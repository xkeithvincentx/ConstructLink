<?php
/**
 * ConstructLink™ Request View Details - Unified Request Management
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <!-- Action Buttons -->
        <?php if ($request['status'] === 'Draft' && $request['requested_by'] == $user['id']): ?>
            <div class="btn-group me-2">
                <form method="POST" action="?route=requests/submit&id=<?= $request['id'] ?>" style="display: inline;">
                    <?= CSRFProtection::getTokenField() ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit this request for review?')">
                        <i class="bi bi-send me-1"></i>Submit Request
                    </button>
                </form>
            </div>
        <?php endif; ?>
        <?php if ($request['status'] === 'Submitted' && in_array($user['role_name'], $roleConfig['requests/review'] ?? [])): ?>
            <div class="btn-group me-2">
                <a href="?route=requests/review&id=<?= $request['id'] ?>" class="btn btn-info">
                    <i class="bi bi-eye-fill me-1"></i>Review Request
                </a>
            </div>
        <?php endif; ?>
        <?php if (in_array($request['status'], ['Reviewed', 'Forwarded']) && in_array($user['role_name'], $roleConfig['requests/approve'] ?? [])): ?>
            <div class="btn-group me-2">
                <a href="?route=requests/approve&id=<?= $request['id'] ?>" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Approve/Decline
                </a>
            </div>
        <?php endif; ?>
        <?php if ($request['status'] === 'Approved' && in_array($user['role_name'], $roleConfig['requests/generate-po'] ?? []) && empty($request['procurement_id'])): ?>
            <div class="btn-group">
                <a href="?route=requests/generate-po&request_id=<?= $request['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create PO
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Request Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Request ID:</dt>
                            <dd class="col-sm-7">#<?= $request['id'] ?></dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $statusClass = [
                                    'Draft' => 'bg-secondary',
                                    'Submitted' => 'bg-info',
                                    'Reviewed' => 'bg-warning',
                                    'Forwarded' => 'bg-primary',
                                    'Approved' => 'bg-success',
                                    'Declined' => 'bg-danger',
                                    'Procured' => 'bg-dark',
                                    'Fulfilled' => 'bg-success'
                                ];
                                $class = $statusClass[$request['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $class ?>">
                                    <?= htmlspecialchars($request['status']) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Request Type:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($request['request_type']) ?>
                                </span>
                            </dd>
                            
                            <?php if ($request['category']): ?>
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($request['category']) ?></dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-5">Urgency:</dt>
                            <dd class="col-sm-7">
                                <?php
                                $urgencyClass = [
                                    'Normal' => 'bg-secondary',
                                    'Urgent' => 'bg-warning',
                                    'Critical' => 'bg-danger'
                                ];
                                $class = $urgencyClass[$request['urgency']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $class ?>">
                                    <?= htmlspecialchars($request['urgency']) ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Requested By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($request['requested_by_name']) ?></dd>
                            
                            <dt class="col-sm-5">Request Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y g:i A', strtotime($request['created_at'])) ?></dd>
                            
                            <?php if ($request['reviewed_by_name']): ?>
                            <dt class="col-sm-5">Reviewed By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($request['reviewed_by_name']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($request['approved_by_name']): ?>
                            <dt class="col-sm-5">Approved By:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($request['approved_by_name']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if ($request['date_needed']): ?>
                            <dt class="col-sm-5">Date Needed:</dt>
                            <dd class="col-sm-7">
                                <span class="<?= strtotime($request['date_needed']) < time() ? 'text-danger fw-bold' : '' ?>">
                                    <?= date('M j, Y', strtotime($request['date_needed'])) ?>
                                </span>
                                <?php if (strtotime($request['date_needed']) < time()): ?>
                                    <small class="text-danger">(Overdue)</small>
                                <?php endif; ?>
                            </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="mt-3">
                    <h6>Description:</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($request['description'])) ?></p>
                </div>
                
                <!-- Quantity and Cost Information -->
                <?php if ($request['quantity'] || $request['estimated_cost']): ?>
                <div class="row mt-3">
                    <?php if ($request['quantity']): ?>
                    <div class="col-md-6">
                        <h6>Quantity Information:</h6>
                        <p class="text-muted">
                            <strong><?= number_format($request['quantity']) ?></strong>
                            <?= $request['unit'] ? htmlspecialchars($request['unit']) : 'units' ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['estimated_cost']): ?>
                    <div class="col-md-6">
                        <h6>Estimated Cost:</h6>
                        <p class="text-muted">
                            <strong>₱<?= number_format($request['estimated_cost'], 2) ?></strong>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Additional Remarks -->
                <?php if ($request['remarks']): ?>
                <div class="mt-3">
                    <h6>Additional Remarks:</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($request['remarks'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Activity Timeline -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Activity Timeline
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($requestLogs)): ?>
                    <div class="timeline">
                        <?php foreach ($requestLogs as $log): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">
                                        <?= ucwords(str_replace('_', ' ', $log['action'])) ?>
                                    </h6>
                                    <p class="timeline-text">
                                        <?php if ($log['old_status'] && $log['new_status']): ?>
                                            Status changed from <strong><?= htmlspecialchars($log['old_status']) ?></strong> 
                                            to <strong><?= htmlspecialchars($log['new_status']) ?></strong>
                                        <?php endif; ?>
                                        <?php if ($log['remarks']): ?>
                                            <br><em><?= htmlspecialchars($log['remarks']) ?></em>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?> • 
                                        <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No activity logs available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Project Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-building me-2"></i>Project Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <div class="fw-medium"><?= htmlspecialchars($request['project_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($request['project_code']) ?></small>
                    </dd>
                </dl>
                
                <div class="mt-3">
                    <a href="?route=projects/view&id=<?= $request['project_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Project Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Procurement & Delivery Tracking -->
        <?php if (isset($procurementOrders) && !empty($procurementOrders)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-cart me-2"></i>Procurement & Delivery Status
                </h6>
            </div>
            <div class="card-body">
                <?php foreach ($procurementOrders as $po): ?>
                <div class="procurement-order-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <a href="?route=procurement-orders/view&id=<?= $po['id'] ?>" class="text-decoration-none">
                                    PO #<?= htmlspecialchars($po['po_number']) ?>
                                </a>
                            </h6>
                            <small class="text-muted"><?= htmlspecialchars($po['title']) ?></small>
                        </div>
                        <div class="text-end">
                            <?php
                            $statusClass = [
                                'Draft' => 'bg-secondary',
                                'Pending' => 'bg-warning',
                                'Approved' => 'bg-success',
                                'Rejected' => 'bg-danger',
                                'Scheduled for Delivery' => 'bg-info',
                                'In Transit' => 'bg-primary',
                                'Delivered' => 'bg-success',
                                'Received' => 'bg-dark'
                            ];
                            $class = $statusClass[$po['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $class ?> mb-1">
                                <?= htmlspecialchars($po['status']) ?>
                            </span>
                            
                            <!-- Overall Delivery Status Badge -->
                            <?php if (isset($request['overall_delivery_status'])): ?>
                            <?php
                            $deliveryStatusClass = [
                                'Completed' => 'bg-success',
                                'In Progress' => 'bg-primary',
                                'Scheduled' => 'bg-info',
                                'Ready for Delivery' => 'bg-warning',
                                'Processing' => 'bg-secondary',
                                'Awaiting Procurement' => 'bg-light text-dark',
                                'Not Started' => 'bg-light text-muted'
                            ];
                            $deliveryClass = $deliveryStatusClass[$request['overall_delivery_status']] ?? 'bg-secondary';
                            ?>
                            <br>
                            <span class="badge <?= $deliveryClass ?> small">
                                <?= htmlspecialchars($request['overall_delivery_status']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Delivery Status -->
                    <?php if ($po['delivery_status'] && $po['delivery_status'] !== 'Pending'): ?>
                    <div class="delivery-status mb-2">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-truck me-2 text-primary"></i>
                            <strong>Delivery Status:</strong>
                            <?php
                            $deliveryStatusClass = [
                                'Pending' => 'bg-secondary',
                                'Scheduled' => 'bg-info',
                                'In Transit' => 'bg-warning',
                                'Delivered' => 'bg-success',
                                'Received' => 'bg-dark',
                                'Partial' => 'bg-warning'
                            ];
                            $deliveryClass = $deliveryStatusClass[$po['delivery_status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $deliveryClass ?> ms-2">
                                <?= htmlspecialchars($po['delivery_status']) ?>
                            </span>
                        </div>
                        
                        <!-- Delivery Details -->
                        <div class="delivery-details small text-muted">
                            <?php if ($po['delivery_method']): ?>
                                <div><i class="bi bi-box me-1"></i>Method: <?= htmlspecialchars($po['delivery_method']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($po['tracking_number']): ?>
                                <div><i class="bi bi-hash me-1"></i>Tracking: <?= htmlspecialchars($po['tracking_number']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($po['scheduled_delivery_date']): ?>
                                <div><i class="bi bi-calendar-event me-1"></i>Scheduled: <?= date('M j, Y', strtotime($po['scheduled_delivery_date'])) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($po['actual_delivery_date']): ?>
                                <div><i class="bi bi-calendar-check me-1"></i>Delivered: <?= date('M j, Y', strtotime($po['actual_delivery_date'])) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($po['delivery_location']): ?>
                                <div><i class="bi bi-geo-alt me-1"></i>Location: <?= htmlspecialchars($po['delivery_location']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Progress Indicator -->
                    <div class="progress-indicator mb-2">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Request</span>
                            <span>Procurement</span>
                            <span>Delivery</span>
                            <span>Receipt</span>
                        </div>
                        <div class="progress" style="height: 4px;">
                            <?php
                            $progress = 25; // Default for approved request
                            if ($po['status'] === 'Approved') $progress = 50;
                            if (in_array($po['delivery_status'], ['Scheduled', 'In Transit'])) $progress = 75;
                            if (in_array($po['delivery_status'], ['Delivered', 'Received'])) $progress = 100;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Cost Information -->
                    <?php if ($po['net_total']): ?>
                    <div class="cost-info small">
                        <strong>Total Amount: ₱<?= number_format($po['net_total'], 2) ?></strong>
                        <?php if ($po['vendor_name']): ?>
                            <span class="text-muted">• Vendor: <?= htmlspecialchars($po['vendor_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons mt-2">
                        <div class="btn-group btn-group-sm">
                            <a href="?route=procurement-orders/view&id=<?= $po['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>View PO
                            </a>
                            
                            <?php if ($po['status'] === 'Approved' && $po['delivery_status'] === 'Pending' && in_array($user['role_name'], $roleConfig['procurement-orders/schedule-delivery'] ?? [])): ?>
                                <a href="?route=procurement-orders/schedule-delivery&id=<?= $po['id'] ?>" class="btn btn-outline-info">
                                    <i class="bi bi-calendar-plus me-1"></i>Schedule Delivery
                                </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($po['delivery_status'], ['Scheduled', 'In Transit']) && in_array($user['role_name'], $roleConfig['procurement-orders/update-delivery'] ?? [])): ?>
                                <a href="?route=procurement-orders/update-delivery&id=<?= $po['id'] ?>" class="btn btn-outline-warning">
                                    <i class="bi bi-truck me-1"></i>Update Status
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($po['delivery_status'] === 'Delivered' && in_array($user['role_name'], $roleConfig['procurement-orders/receive'] ?? [])): ?>
                                <a href="?route=procurement-orders/receive&id=<?= $po['id'] ?>" class="btn btn-outline-success">
                                    <i class="bi bi-check-circle me-1"></i>Confirm Receipt
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Delivery Discrepancy Alert -->
                    <?php if ($po['delivery_discrepancy_notes']): ?>
                    <div class="alert alert-warning mt-2 mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Delivery Discrepancy:</strong>
                        <div class="small mt-1"><?= nl2br(htmlspecialchars($po['delivery_discrepancy_notes'])) ?></div>
                        
                        <!-- Discrepancy Resolution Actions -->
                        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/resolve-discrepancy'] ?? [])): ?>
                        <div class="mt-2">
                            <a href="?route=procurement-orders/resolve-discrepancy&id=<?= $po['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check-circle me-1"></i>Resolve Discrepancy
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Overdue Delivery Alert -->
                    <?php if (isset($po['scheduled_delivery_date']) && strtotime($po['scheduled_delivery_date']) < time() && !in_array($po['delivery_status'], ['Delivered', 'Received'])): ?>
                    <div class="alert alert-danger mt-2 mb-0">
                        <i class="bi bi-clock me-2"></i>
                        <strong>Overdue Delivery:</strong>
                        <div class="small mt-1">
                            Expected delivery: <?= date('M j, Y', strtotime($po['scheduled_delivery_date'])) ?>
                            (<?= abs(floor((time() - strtotime($po['scheduled_delivery_date'])) / 86400)) ?> days overdue)
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Summary Stats -->
                <?php if (count($procurementOrders) > 1): ?>
                <div class="procurement-summary mt-3 pt-3 border-top">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="small text-muted">Total POs</div>
                            <div class="fw-bold"><?= count($procurementOrders) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Total Value</div>
                            <div class="fw-bold">₱<?= number_format(array_sum(array_column($procurementOrders, 'net_total')), 2) ?></div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Completed</div>
                            <div class="fw-bold">
                                <?= count(array_filter($procurementOrders, function($po) { return $po['delivery_status'] === 'Received'; })) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($request['status'] === 'Approved'): ?>
        <!-- No Procurement Orders Yet -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-cart me-2"></i>Procurement Status
                </h6>
            </div>
            <div class="card-body text-center">
                <div class="text-muted mb-3">
                    <i class="bi bi-cart-plus display-4"></i>
                </div>
                <h6>Ready for Procurement</h6>
                <p class="text-muted small">This request has been approved and is ready for procurement order creation.</p>
                
                <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/createFromRequest'] ?? [])): ?>
                <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-cart-plus me-1"></i>Create Procurement Order
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($request['status'] === 'Draft' && $request['requested_by'] == $user['id']): ?>
                        <form method="POST" action="?route=requests/submit&id=<?= $request['id'] ?>">
                            <?= CSRFProtection::getTokenField() ?>
                            <button type="submit" class="btn btn-primary btn-sm w-100" onclick="return confirm('Are you sure you want to submit this request for review?')">
                                <i class="bi bi-send me-1"></i>Submit Request
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'Submitted' && in_array($user['role_name'], $roleConfig['requests/review'] ?? [])): ?>
                        <a href="?route=requests/review&id=<?= $request['id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye-fill me-1"></i>Review Request
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($request['status'], ['Reviewed', 'Forwarded']) && in_array($user['role_name'], $roleConfig['requests/approve'] ?? [])): ?>
                        <a href="?route=requests/approve&id=<?= $request['id'] ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle me-1"></i>Approve/Decline
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'Approved' && in_array($user['role_name'], $roleConfig['procurement-orders/createFromRequest'] ?? []) && empty($request['procurement_id'])): ?>
                        <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-cart-plus me-1"></i>Create Procurement Order
                        </a>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print Request
                    </button>
                    
                    <a href="?route=requests" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list me-1"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Request Statistics -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Request Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-value text-primary">
                                <?php
                                $createdDate = new DateTime($request['created_at']);
                                $now = new DateTime();
                                $diff = $now->diff($createdDate);
                                echo $diff->days;
                                ?>
                            </div>
                            <div class="stat-label">Days Old</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-item">
                            <div class="stat-value text-info">
                                <?= count($requestLogs ?? []) ?>
                            </div>
                            <div class="stat-label">Activities</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    width: 2px;
    height: calc(100% + 10px);
    background-color: #dee2e6;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.stat-item {
    padding: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

@media print {
    .btn-toolbar,
    .card:not(:first-child) {
        display: none !important;
    }
}
</style>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Request Details - ConstructLink™';
$pageHeader = 'Request Details #' . $request['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests'],
    ['title' => 'Request #' . $request['id'], 'url' => '?route=requests/view&id=' . $request['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
