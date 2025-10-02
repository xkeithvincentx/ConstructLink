<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-cart-plus me-2"></i>
        Procurement Orders
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
            <a href="?route=procurement-orders/create" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-1"></i>Create Procurement Order
            </a>
            <a href="?route=procurement-orders/create-retrospective" class="btn btn-warning me-2" 
               title="Document purchases made without PO">
                <i class="bi bi-clock-history me-1"></i>Retroactive PO
            </a>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/export'] ?? [])): ?>
            <a href="?route=procurement-orders/export<?= !empty($_GET) ? '&' . http_build_query($_GET) : '' ?>" class="btn btn-success me-2">
                <i class="bi bi-download me-1"></i>Export
            </a>
        <?php endif; ?>
        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/approved-requests'] ?? [])): ?>
            <a href="?route=procurement-orders/approved-requests" class="btn btn-info">
                <i class="bi bi-list-check me-1"></i>Approved Requests
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- MVA Workflow Sidebar -->
<div class="alert alert-info mb-4">
    <strong>MVA Workflow:</strong> <br>
    <span class="badge bg-primary">Maker</span> (Procurement Officer) →
    <span class="badge bg-warning text-dark">Verifier</span> (Asset Director) →
    <span class="badge bg-success">Authorizer</span> (Finance Director) →
    <span class="badge bg-info">Scheduled</span> →
    <span class="badge bg-secondary">Delivered</span> →
    <span class="badge bg-dark">Received</span>
</div>

<!-- Role-Based Statistics Cards -->
<div class="row mb-4">
    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer'])): ?>
        <!-- Pending Verification (for Verifiers) -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pending Verification</h6>
                            <h3 class="mb-0"><?= $stats['pending_verification'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-clock display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
        <!-- Pending Approval (for Authorizers) -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pending Approval</h6>
                            <h3 class="mb-0"><?= $stats['pending_approval'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-hourglass-split display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Asset Director'])): ?>
        <!-- Approved Orders -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Approved</h6>
                            <h3 class="mb-0"><?= $stats['approved'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-check-circle display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Project Manager'])): ?>
        <!-- In Transit Orders -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">In Transit</h6>
                            <h3 class="mb-0"><?= $stats['in_transit'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-truck display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
        <!-- Delivered Orders -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Delivered</h6>
                            <h3 class="mb-0"><?= $stats['delivered'] ?? 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-box-seam display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
        <!-- Total Value (Financial Overview) -->
        <div class="col-xl-2 col-md-3 col-sm-6">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Value</h6>
                            <h4 class="mb-0">₱<?= number_format($stats['total_value'] ?? 0, 2) ?></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-currency-dollar display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delivery Alerts Section -->
<?php if (isset($deliveryAlerts) && !empty($deliveryAlerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delivery Alerts
                    <span class="badge bg-dark ms-2"><?= count($deliveryAlerts) ?></span>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (array_slice($deliveryAlerts, 0, 4) as $alert): ?>
                    <div class="col-md-3">
                        <div class="alert alert-<?= ($alert['alert_type'] ?? '') === 'Overdue' ? 'danger' : (($alert['alert_type'] ?? '') === 'Discrepancy' ? 'warning' : 'info') ?> mb-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($alert['alert_type'] ?? 'Alert') ?></strong>
                                    <div class="small mt-1"><?= htmlspecialchars(
                                        ($alert['alert_type'] ?? '') === 'Overdue' 
                                        ? 'Delivery overdue by ' . ($alert['days_overdue'] ?? 0) . ' days'
                                        : (($alert['alert_type'] ?? '') === 'Discrepancy' 
                                           ? 'Delivery discrepancy noted'
                                           : 'Delivery alert')
                                    ) ?></div>
                                    <small class="text-muted">PO #<?= htmlspecialchars($alert['po_number'] ?? '') ?></small>
                                </div>
                                <a href="?route=procurement-orders/view&id=<?= $alert['id'] ?? '' ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($deliveryAlerts) > 4): ?>
                <div class="text-center">
                    <button class="btn btn-sm btn-outline-warning" onclick="toggleAllAlerts()">
                        <i class="bi bi-chevron-down me-1"></i>Show All Alerts (<?= count($deliveryAlerts) - 4 ?> more)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=procurement-orders" class="row g-3">
            <!-- Status Filter - Role-based Options -->
            <div class="col-lg-2 col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?= ($_GET['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>📝 Draft</option>
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                        <option value="Pending Verification" <?= ($_GET['status'] ?? '') === 'Pending Verification' ? 'selected' : '' ?>>
                            📋 Pending Verification
                        </option>
                    <?php endif; ?>
                    <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                        <option value="Pending Approval" <?= ($_GET['status'] ?? '') === 'Pending Approval' ? 'selected' : '' ?>>
                            ⏳ Pending Approval
                        </option>
                    <?php endif; ?>
                    <option value="Approved" <?= ($_GET['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>✅ Approved</option>
                    <option value="Scheduled for Delivery" <?= ($_GET['status'] ?? '') === 'Scheduled for Delivery' ? 'selected' : '' ?>>📅 Scheduled for Delivery</option>
                    <option value="In Transit" <?= ($_GET['status'] ?? '') === 'In Transit' ? 'selected' : '' ?>>🚛 In Transit</option>
                    <option value="Delivered" <?= ($_GET['status'] ?? '') === 'Delivered' ? 'selected' : '' ?>>📦 Delivered</option>
                    <option value="Received" <?= ($_GET['status'] ?? '') === 'Received' ? 'selected' : '' ?>>✔️ Received</option>
                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                        <option value="Rejected" <?= ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>❌ Rejected</option>
                        <option value="Canceled" <?= ($_GET['status'] ?? '') === 'Canceled' ? 'selected' : '' ?>>🚫 Canceled</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <!-- Priority Filter - For Management Roles -->
            <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director', 'Procurement Officer'])): ?>
                <div class="col-lg-2 col-md-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <option value="overdue_delivery" <?= ($_GET['priority'] ?? '') === 'overdue_delivery' ? 'selected' : '' ?>>🚨 Overdue Delivery</option>
                        <option value="pending_action" <?= ($_GET['priority'] ?? '') === 'pending_action' ? 'selected' : '' ?>>🔄 Needs My Action</option>
                        <option value="high_value" <?= ($_GET['priority'] ?? '') === 'high_value' ? 'selected' : '' ?>>💰 High Value (>₱50k)</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <!-- Project Filter -->
            <div class="col-lg-2 col-md-3">
                <label for="project_id" class="form-label">Project</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">All Projects</option>
                    <?php if (isset($projects) && is_array($projects)): ?>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                    <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <!-- Vendor Filter -->
            <div class="col-lg-2 col-md-3">
                <label for="vendor_id" class="form-label">Vendor</label>
                <select class="form-select" id="vendor_id" name="vendor_id">
                    <option value="">All Vendors</option>
                    <?php if (isset($vendors) && is_array($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?= $vendor['id'] ?>" 
                                    <?= ($_GET['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vendor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
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
                       placeholder="PO number, title, vendor..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <!-- Action Buttons -->
            <div class="col-12 d-flex align-items-end gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=procurement-orders" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                
                <!-- Quick Action Buttons -->
                <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                    <button type="button" class="btn btn-outline-warning" onclick="filterByStatus('Pending Verification')">
                        <i class="bi bi-clock me-1"></i>My Verifications
                    </button>
                <?php endif; ?>
                <?php if ($auth->hasRole(['System Admin', 'Finance Director'])): ?>
                    <button type="button" class="btn btn-outline-info" onclick="filterByStatus('Pending Approval')">
                        <i class="bi bi-shield-check me-1"></i>My Approvals
                    </button>
                <?php endif; ?>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <button type="button" class="btn btn-outline-success" onclick="filterByStatus('Approved')">
                        <i class="bi bi-truck me-1"></i>Ready for Delivery
                    </button>
                <?php endif; ?>
                <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])): ?>
                    <button type="button" class="btn btn-outline-primary" onclick="filterByStatus('Delivered')">
                        <i class="bi bi-box-seam me-1"></i>For Receipt
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

    <!-- Procurement Orders Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Procurement Orders</h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($procurementOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="procurementOrdersTable">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Title & Details</th>
                                <th>Vendor</th>
                                <th>Project</th>
                                <th>Items</th>
                                <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                    <th>Financial Summary</th>
                                <?php endif; ?>
                                <th>Status & Progress</th>
                                <th>Delivery Info</th>
                                <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                    <th>MVA Workflow</th>
                                <?php endif; ?>
                                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Project Manager'])): ?>
                                    <th>Request Info</th>
                                <?php endif; ?>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procurementOrders as $order): ?>
                                <?php
                                $isOverdue = !empty($order['scheduled_delivery_date']) && 
                                           strtotime($order['scheduled_delivery_date']) < time() && 
                                           !in_array($order['delivery_status'], ['Delivered', 'Received']);
                                $isHighValue = ($order['net_total'] ?? 0) > 50000;
                                $rowClass = $isOverdue ? 'table-warning' : '';
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <!-- PO Number with Priority Indicators -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($isOverdue): ?>
                                                <i class="bi bi-exclamation-triangle-fill text-danger me-1" title="Overdue Delivery"></i>
                                            <?php elseif ($isHighValue && $auth->hasRole(['System Admin', 'Finance Director'])): ?>
                                                <i class="bi bi-gem text-warning me-1" title="High Value Order"></i>
                                            <?php endif; ?>
                                            <a href="?route=procurement-orders/view&id=<?= $order['id'] ?>" class="text-decoration-none fw-medium">
                                                <?= htmlspecialchars($order['po_number'] ?? 'DRAFT-' . $order['id']) ?>
                                            </a>
                                        </div>
                                        <?php if (!empty($order['request_id'])): ?>
                                            <small class="text-primary">
                                                <i class="bi bi-link-45deg"></i>From Request #<?= $order['request_id'] ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                        <!-- Retroactive and File Indicators -->
                                        <div class="mt-1">
                                            <?php if (!empty($order['is_retroactive']) && $order['is_retroactive'] == 1): ?>
                                                <span class="badge bg-warning text-dark me-1" title="Retroactive PO - Post-purchase documentation">
                                                    <i class="bi bi-clock-history"></i> Retroactive
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $fileCount = 0;
                                            $fileTypes = [];
                                            if (!empty($order['quote_file'])) { $fileCount++; $fileTypes[] = 'Quote'; }
                                            if (!empty($order['purchase_receipt_file'])) { $fileCount++; $fileTypes[] = 'Receipt'; }
                                            if (!empty($order['supporting_evidence_file'])) { $fileCount++; $fileTypes[] = 'Evidence'; }
                                            ?>
                                            <?php if ($fileCount > 0): ?>
                                                <span class="badge bg-info text-white" title="<?= $fileCount ?> file(s): <?= implode(', ', $fileTypes) ?>">
                                                    <i class="bi bi-paperclip"></i> <?= $fileCount ?> file<?= $fileCount > 1 ? 's' : '' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Enhanced Title & Details -->
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($order['title']) ?></div>
                                        <?php if (!empty($order['package_scope'])): ?>
                                            <small class="text-muted">
                                                <?= htmlspecialchars(substr($order['package_scope'], 0, 60)) ?>
                                                <?= strlen($order['package_scope']) > 60 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer']) && !empty($order['urgency'])): ?>
                                            <br><span class="badge <?= $order['urgency'] === 'high' ? 'bg-danger' : ($order['urgency'] === 'medium' ? 'bg-warning text-dark' : 'bg-secondary') ?> badge-sm">
                                                <?= ucfirst($order['urgency']) ?> Priority
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Vendor with Contact Info -->
                                    <td>
                                        <div><?= htmlspecialchars($order['vendor_name']) ?></div>
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer']) && !empty($order['vendor_contact'])): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($order['vendor_contact']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Project Info -->
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($order['project_name']) ?>
                                        </span>
                                        <?php if (!empty($order['project_code'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($order['project_code']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Items Summary -->
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $order['item_count'] ?> items</span>
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer']) && ($order['item_count'] ?? 0) > 10): ?>
                                            <br><small class="text-warning">
                                                <i class="bi bi-exclamation-circle"></i> Large Order
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Financial Summary (Finance/Asset Director Only) -->
                                    <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                                        <td>
                                            <div class="fw-medium">₱<?= number_format($order['net_total'], 2) ?></div>
                                            <?php if ($order['subtotal'] != $order['net_total']): ?>
                                                <small class="text-muted">
                                                    Subtotal: ₱<?= number_format($order['subtotal'], 2) ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($order['vat_amount']) && $order['vat_amount'] > 0): ?>
                                                <br><small class="text-info">
                                                    VAT: ₱<?= number_format($order['vat_amount'], 2) ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($isHighValue): ?>
                                                <br><span class="badge bg-warning text-dark">High Value</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <!-- Enhanced Status & Progress -->
                                    <td>
                                        <?php
                                        $statusConfig = [
                                            'Draft' => ['class' => 'bg-secondary', 'icon' => 'file-text', 'progress' => 10],
                                            'Pending Verification' => ['class' => 'bg-primary', 'icon' => 'clock', 'progress' => 25],
                                            'Pending Approval' => ['class' => 'bg-warning text-dark', 'icon' => 'hourglass-split', 'progress' => 50],
                                            'Approved' => ['class' => 'bg-success', 'icon' => 'check-circle', 'progress' => 75],
                                            'Scheduled for Delivery' => ['class' => 'bg-info', 'icon' => 'calendar-check', 'progress' => 80],
                                            'In Transit' => ['class' => 'bg-primary', 'icon' => 'truck', 'progress' => 85],
                                            'Delivered' => ['class' => 'bg-secondary', 'icon' => 'box-seam', 'progress' => 90],
                                            'Received' => ['class' => 'bg-dark', 'icon' => 'check-square', 'progress' => 100],
                                            'Rejected' => ['class' => 'bg-danger', 'icon' => 'x-circle', 'progress' => 0],
                                            'Canceled' => ['class' => 'bg-dark', 'icon' => 'slash-circle', 'progress' => 0]
                                        ];
                                        $config = $statusConfig[$order['status']] ?? ['class' => 'bg-secondary', 'icon' => 'question', 'progress' => 0];
                                        ?>
                                        <div class="status-cell">
                                            <span class="badge <?= $config['class'] ?> mb-1">
                                                <i class="bi bi-<?= $config['icon'] ?> me-1"></i>
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                            
                                            <!-- Progress Bar -->
                                            <div class="progress mb-1" style="height: 4px;">
                                                <div class="progress-bar <?= str_replace(['bg-', ' text-dark'], ['bg-', ''], $config['class']) ?>" 
                                                     style="width: <?= $config['progress'] ?>%"></div>
                                            </div>
                                            
                                            <!-- Delivery Status Badge -->
                                            <?php
                                            $deliveryStatusClasses = [
                                                'Pending' => 'bg-light text-dark',
                                                'Scheduled' => 'bg-info',
                                                'In Transit' => 'bg-primary',
                                                'Delivered' => 'bg-success',
                                                'Received' => 'bg-dark',
                                                'Partial' => 'bg-warning text-dark'
                                            ];
                                            $deliveryStatus = $order['delivery_status'] ?? 'Pending';
                                            $deliveryStatusClass = $deliveryStatusClasses[$deliveryStatus] ?? 'bg-secondary';
                                            ?>
                                            <small>
                                                <span class="badge <?= $deliveryStatusClass ?> badge-sm">
                                                    <?= htmlspecialchars($deliveryStatus) ?>
                                                </span>
                                            </small>
                                        </div>
                                    </td>
                                    
                                    <!-- Enhanced Delivery Info -->
                                    <td>
                                        <?php if (!empty($order['tracking_number'])): ?>
                                            <div class="small fw-medium">
                                                <i class="bi bi-truck me-1"></i><?= htmlspecialchars($order['tracking_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($order['delivery_method'])): ?>
                                            <?php
                                            $serviceDeliveryMethods = ['On-site Service', 'Remote Service', 'Digital Delivery', 'Email Delivery', 'Postal Mail', 'Office Pickup', 'Service Completion', 'N/A'];
                                            $isServiceDelivery = in_array($order['delivery_method'], $serviceDeliveryMethods);
                                            $deliveryIcon = $isServiceDelivery ? 'bi-gear' : 'bi-geo-alt';
                                            ?>
                                            <div class="small text-muted">
                                                <i class="bi <?= $deliveryIcon ?> me-1"></i><?= htmlspecialchars($order['delivery_method']) ?>
                                                <?php if ($isServiceDelivery): ?>
                                                    <span class="badge bg-info badge-sm ms-1">Service</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($order['scheduled_delivery_date'])): ?>
                                            <div class="small">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?php
                                                $scheduledDate = strtotime($order['scheduled_delivery_date']);
                                                ?>
                                                <span class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                    <?= date('M j, Y', $scheduledDate) ?>
                                                </span>
                                                <?php if ($isOverdue): ?>
                                                    <br><span class="badge bg-danger badge-sm">
                                                        <?= abs(floor((time() - $scheduledDate) / 86400)) ?> days overdue
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($order['delivery_location'])): ?>
                                            <?php
                                            $serviceLocations = ['Client Office', 'Service Provider Office', 'Digital/Email', 'Multiple Locations', 'N/A'];
                                            $isServiceLocation = in_array($order['delivery_location'], $serviceLocations);
                                            $locationIcon = $isServiceLocation ? 'bi-building' : 'bi-pin-map';
                                            ?>
                                            <div class="small text-muted">
                                                <i class="bi <?= $locationIcon ?> me-1"></i><?= htmlspecialchars(substr($order['delivery_location'], 0, 20)) ?>
                                                <?= strlen($order['delivery_location']) > 20 ? '...' : '' ?>
                                                <?php if ($isServiceLocation): ?>
                                                    <span class="badge bg-info badge-sm ms-1">Service</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- MVA Workflow (Management Roles) -->
                                    <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Finance Director'])): ?>
                                        <td>
                                            <div class="mva-workflow small">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge badge-sm bg-light text-dark me-1">M</span>
                                                    <span class="text-truncate" style="max-width: 80px;">
                                                        <?= htmlspecialchars($order['requested_by_name'] ?? 'Unknown') ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if (!empty($order['verified_by_name'])): ?>
                                                    <div class="d-flex align-items-center mb-1">
                                                        <span class="badge badge-sm bg-warning text-dark me-1">V</span>
                                                        <span class="text-truncate" style="max-width: 80px;">
                                                            <?= htmlspecialchars($order['verified_by_name']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($order['approved_by_name'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge badge-sm bg-success me-1">A</span>
                                                        <span class="text-truncate" style="max-width: 80px;">
                                                            <?= htmlspecialchars($order['approved_by_name']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <!-- Request Info (for tracking) -->
                                    <?php if ($auth->hasRole(['System Admin', 'Procurement Officer', 'Project Manager'])): ?>
                                        <td>
                                            <div class="request-info small">
                                                <div class="fw-medium"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('g:i A', strtotime($order['created_at'])) ?></small>
                                                
                                                <?php if (!empty($order['date_needed'])): ?>
                                                    <br><span class="text-info">
                                                        <i class="bi bi-calendar-event"></i> Needed: <?= date('M j', strtotime($order['date_needed'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($order['creator_role'])): ?>
                                                    <br><span class="badge bg-light text-dark badge-sm">
                                                        <?= htmlspecialchars($order['creator_role']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                    <!-- Enhanced Role-Based Actions -->
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <!-- Primary Action Button (Most Relevant for Current Role) -->
                                            <?php
                                            $primaryAction = null;
                                            $secondaryActions = [];
                                            
                                            // Determine primary action based on role and status
                                            if ($order['status'] === 'Pending Verification' && $auth->hasRole(['System Admin', 'Asset Director'])):
                                                $primaryAction = [
                                                    'url' => "?route=procurement-orders/verify&id={$order['id']}",
                                                    'class' => 'btn-warning',
                                                    'icon' => 'check-circle',
                                                    'text' => 'Verify',
                                                    'title' => 'Verify this procurement order'
                                                ];
                                            elseif ($order['status'] === 'Pending Approval' && $auth->hasRole(['System Admin', 'Finance Director'])):
                                                $primaryAction = [
                                                    'url' => "?route=procurement-orders/approve&id={$order['id']}",
                                                    'class' => 'btn-success',
                                                    'icon' => 'shield-check',
                                                    'text' => 'Approve',
                                                    'title' => 'Approve this procurement order'
                                                ];
                                            elseif ($order['status'] === 'Approved' && $auth->hasRole(['System Admin', 'Procurement Officer'])):
                                                $primaryAction = [
                                                    'url' => "?route=procurement-orders/update-delivery&id={$order['id']}",
                                                    'class' => 'btn-primary',
                                                    'icon' => 'truck',
                                                    'text' => 'Update Delivery',
                                                    'title' => 'Update delivery status for this order'
                                                ];
                                            elseif ($order['status'] === 'Delivered' && $auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])):
                                                $primaryAction = [
                                                    'url' => "?route=procurement-orders/receive&id={$order['id']}",
                                                    'class' => $isOverdue ? 'btn-danger' : 'btn-success',
                                                    'icon' => 'check-square',
                                                    'text' => $isOverdue ? 'Receive Overdue' : 'Confirm Receipt',
                                                    'title' => 'Confirm receipt of delivered items'
                                                ];
                                            elseif (in_array($order['status'], ['Scheduled for Delivery', 'In Transit']) && $auth->hasRole(['System Admin', 'Procurement Officer'])):
                                                $primaryAction = [
                                                    'url' => "?route=procurement-orders/update-delivery&id={$order['id']}",
                                                    'class' => 'btn-primary',
                                                    'icon' => 'truck',
                                                    'text' => 'Update Delivery',
                                                    'title' => 'Update delivery status'
                                                ];
                                            endif;
                                            
                                            // Always available: View Details
                                            $viewAction = [
                                                'url' => "?route=procurement-orders/view&id={$order['id']}",
                                                'class' => 'btn-outline-primary',
                                                'icon' => 'eye',
                                                'text' => '',
                                                'title' => 'View full details'
                                            ];
                                            
                                            // Secondary actions based on role and status
                                            if (in_array($order['status'], ['Draft', 'Pending Verification']) && $auth->hasRole(['System Admin', 'Procurement Officer'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=procurement-orders/edit&id={$order['id']}",
                                                    'class' => 'btn-outline-warning',
                                                    'icon' => 'pencil',
                                                    'text' => '',
                                                    'title' => 'Edit order details'
                                                ];
                                            endif;
                                            
                                            if ($order['status'] === 'Received' && $auth->hasRole(['System Admin', 'Procurement Officer', 'Warehouseman', 'Site Inventory Clerk', 'Project Manager'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=procurement-orders/generateAssets&id={$order['id']}",
                                                    'class' => 'btn-outline-secondary',
                                                    'icon' => 'plus-square',
                                                    'text' => '',
                                                    'title' => 'Generate assets from this order'
                                                ];
                                            endif;
                                            
                                            if ($order['status'] === 'Delivered' && $auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=procurement-orders/resolve-discrepancy&id={$order['id']}",
                                                    'class' => 'btn-outline-danger',
                                                    'icon' => 'exclamation-triangle',
                                                    'text' => '',
                                                    'title' => 'Report or resolve delivery discrepancy'
                                                ];
                                            endif;
                                            
                                            if (in_array($order['status'], ['Pending Verification', 'Pending Approval', 'Approved']) && 
                                                $auth->hasRole(['System Admin', 'Procurement Officer'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=procurement-orders/cancel&id={$order['id']}",
                                                    'class' => 'btn-outline-danger',
                                                    'icon' => 'x-circle',
                                                    'text' => '',
                                                    'title' => 'Cancel this order'
                                                ];
                                            endif;
                                            
                                            // Print Preview for appropriate statuses
                                            $allowedPrintStatuses = ['Approved', 'Scheduled for Delivery', 'In Transit', 'Delivered', 'Received'];
                                            if (in_array($order['status'], $allowedPrintStatuses) && 
                                                $auth->hasRole(['System Admin', 'Procurement Officer', 'Finance Director'])):
                                                $secondaryActions[] = [
                                                    'url' => "?route=procurement-orders/print-preview&id={$order['id']}",
                                                    'class' => 'btn-outline-primary',
                                                    'icon' => 'printer',
                                                    'text' => '',
                                                    'title' => 'Print preview purchase order'
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
                                            <?php if ($isOverdue && $auth->hasRole(['System Admin', 'Procurement Officer', 'Asset Director'])): ?>
                                                <div class="mt-1">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick="sendDeliveryReminder(<?= $order['id'] ?>)" 
                                                            title="Send delivery reminder to vendor">
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
                <?php if ($pagination['total_pages'] > 1): ?>
                    <nav aria-label="Procurement orders pagination" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagination['has_prev']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=procurement-orders&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?route=procurement-orders&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=procurement-orders&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">No procurement orders found</h5>
                    <p class="text-muted">Try adjusting your filters or create a new procurement order.</p>
                    <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                        <a href="?route=procurement-orders/create" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Create First Procurement Order
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<script>
function refreshTable() {
    window.location.reload();
}

function toggleAllAlerts() {
    const hiddenAlerts = document.querySelectorAll('.hidden-alert');
    const button = event.target;
    
    if (hiddenAlerts.length > 0) {
        hiddenAlerts.forEach(alert => {
            alert.style.display = alert.style.display === 'none' ? 'block' : 'none';
        });
        
        const isShowing = hiddenAlerts[0].style.display !== 'none';
        button.innerHTML = isShowing ? 
            '<i class="bi bi-chevron-up me-1"></i>Hide Additional Alerts' : 
            '<i class="bi bi-chevron-down me-1"></i>Show All Alerts (' + hiddenAlerts.length + ' more)';
    }
}

// Auto-refresh every 5 minutes
setInterval(refreshTable, 300000);

// Enhanced search functionality with debounce
let searchTimeout;
document.getElementById('search').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

document.getElementById('search').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (this.value.length >= 3 || this.value.length === 0) {
            this.form.submit();
        }
    }, 500);
});

// Date range validation
document.getElementById('date_from').addEventListener('change', function() {
    const dateTo = document.getElementById('date_to');
    if (this.value && dateTo.value && this.value > dateTo.value) {
        alert('Start date cannot be later than end date');
        this.value = '';
    }
});

document.getElementById('date_to').addEventListener('change', function() {
    const dateFrom = document.getElementById('date_from');
    if (this.value && dateFrom.value && this.value < dateFrom.value) {
        alert('End date cannot be earlier than start date');
        this.value = '';
    }
});

// Quick filter functions
function filterByStatus(status) {
    document.getElementById('status').value = status;
    document.querySelector('form[action="?route=procurement-orders"]').submit();
}

function filterByPriority(priority) {
    const prioritySelect = document.getElementById('priority');
    if (prioritySelect) {
        prioritySelect.value = priority;
        document.querySelector('form[action="?route=procurement-orders"]').submit();
    }
}

// Send delivery reminder function
function sendDeliveryReminder(orderId) {
    if (confirm('Send delivery reminder to vendor?')) {
        fetch('?route=procurement-orders/sendDeliveryReminder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Delivery reminder sent successfully!');
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
    const overdueRows = document.querySelectorAll('.table-warning');
    overdueRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 0 10px rgba(255, 193, 7, 0.3)';
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
    
    // Auto-submit filters on change
    const filterForm = document.querySelector('form[action="?route=procurement-orders"]');
    const filterInputs = filterForm.querySelectorAll('select, input[name="date_from"], input[name="date_to"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.name !== 'search') {
                filterForm.submit();
            }
        });
    });
});

// Auto-refresh for overdue orders with visual indicator
if (document.querySelector('.table-warning') || document.querySelector('.text-danger')) {
    let refreshTimer = 300; // 5 minutes for overdue items
    
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
$pageTitle = 'Procurement Orders - ConstructLink™';
$pageHeader = 'Procurement Orders';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
