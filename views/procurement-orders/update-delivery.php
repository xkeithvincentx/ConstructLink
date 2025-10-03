<?php
/**
 * ConstructLink™ Update Delivery Status View
 * Update delivery status for procurement orders
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Display Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Update Delivery Status Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-arrow-repeat me-2"></i>Update Delivery Status for PO #<?= htmlspecialchars($procurementOrder['po_number']) ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (!in_array($userRole, $roleConfig['procurement-orders/update-delivery'] ?? [])): ?>
                    <div class="alert alert-danger mt-4">You do not have permission to update delivery status for this procurement order.</div>
                <?php else: ?>
                <form method="POST" action="?route=procurement-orders/update-delivery&id=<?= $procurementOrder['id'] ?>" id="updateDeliveryForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="delivery_status" class="form-label">Delivery Status <span class="text-danger">*</span></label>
                            <select name="delivery_status" id="delivery_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <?php
                                $currentDeliveryStatus = $procurementOrder['delivery_status'] ?? 'Pending';
                                // Show appropriate options based on current delivery status
                                if ($currentDeliveryStatus === 'Pending'): ?>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Delivered">Delivered</option>
                                <?php elseif ($currentDeliveryStatus === 'Scheduled'): ?>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Delayed">Delayed</option>
                                <?php elseif ($currentDeliveryStatus === 'In Transit'): ?>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Delayed">Delayed</option>
                                    <option value="Failed Delivery">Failed Delivery</option>
                                <?php elseif ($currentDeliveryStatus === 'Delayed'): ?>
                                    <option value="In Transit">In Transit</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Failed Delivery">Failed Delivery</option>
                                <?php elseif ($currentDeliveryStatus === 'Failed Delivery'): ?>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Transit">In Transit</option>
                                <?php else: ?>
                                    <!-- For other statuses, show all options -->
                                    <option value="Scheduled" <?= ($currentDeliveryStatus === 'Scheduled') ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="In Transit" <?= ($currentDeliveryStatus === 'In Transit') ? 'selected' : '' ?>>In Transit</option>
                                    <option value="Delivered" <?= ($currentDeliveryStatus === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Delayed" <?= ($currentDeliveryStatus === 'Delayed') ? 'selected' : '' ?>>Delayed</option>
                                    <option value="Failed Delivery" <?= ($currentDeliveryStatus === 'Failed Delivery') ? 'selected' : '' ?>>Failed Delivery</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="actual_date_field" style="display: none;">
                            <label for="actual_date" class="form-label">Actual Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" name="actual_date" id="actual_date" class="form-control" 
                                   max="<?= date('Y-m-d') ?>">
                            <div class="form-text">
                                Required when marking as delivered.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status_notes" class="form-label">Status Notes</label>
                        <textarea name="status_notes" id="status_notes" class="form-control" rows="4" 
                                  placeholder="Enter notes about the delivery status update..."></textarea>
                        <div class="form-text">
                            Provide details about the delivery status, any issues encountered, or additional information.
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Status
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Current Delivery Info -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Delivery Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-6">PO Number:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($procurementOrder['po_number']) ?></dd>
                    
                    <dt class="col-sm-6">Current Status:</dt>
                    <dd class="col-sm-6">
                        <?php
                        $statusClasses = [
                            'Scheduled' => 'bg-info',
                            'In Transit' => 'bg-primary',
                            'Delivered' => 'bg-success',
                            'Delayed' => 'bg-warning',
                            'Failed Delivery' => 'bg-danger'
                        ];
                        $currentStatus = $procurementOrder['delivery_status'] ?? 'Pending';
                        $statusClass = $statusClasses[$currentStatus] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($currentStatus) ?></span>
                    </dd>
                    
                    <?php if (!empty($procurementOrder['delivery_method'])): ?>
                    <dt class="col-sm-6">Method:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($procurementOrder['delivery_method']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['delivery_location'])): ?>
                    <dt class="col-sm-6">Location:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($procurementOrder['delivery_location']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['tracking_number'])): ?>
                    <dt class="col-sm-6">Tracking:</dt>
                    <dd class="col-sm-6"><code><?= htmlspecialchars($procurementOrder['tracking_number']) ?></code></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($procurementOrder['scheduled_delivery_date'])): ?>
                    <dt class="col-sm-6">Scheduled:</dt>
                    <dd class="col-sm-6"><?= date('M j, Y', strtotime($procurementOrder['scheduled_delivery_date'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Status Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Status Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Status Definitions:</h6>
                <ul class="small">
                    <li><strong>In Transit:</strong> Items are on the way to destination</li>
                    <li><strong>Delivered:</strong> Items have arrived at destination</li>
                    <li><strong>Delayed:</strong> Delivery is behind schedule</li>
                    <li><strong>Failed Delivery:</strong> Delivery attempt was unsuccessful</li>
                </ul>
                
                <h6 class="mt-3">Important Notes:</h6>
                <ul class="small">
                    <li>Provide actual delivery date when marking as delivered</li>
                    <li>Include detailed notes for delays or failed deliveries</li>
                    <li>Contact warehouseman for delivery confirmation</li>
                    <li>Update tracking information if available</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide actual date field based on status selection
document.getElementById('delivery_status').addEventListener('change', function() {
    const actualDateField = document.getElementById('actual_date_field');
    const actualDateInput = document.getElementById('actual_date');
    
    if (this.value === 'Delivered') {
        actualDateField.style.display = 'block';
        actualDateInput.required = true;
        // Set default to today
        if (!actualDateInput.value) {
            actualDateInput.value = new Date().toISOString().split('T')[0];
        }
    } else {
        actualDateField.style.display = 'none';
        actualDateInput.required = false;
        actualDateInput.value = '';
    }
});

// Form validation
document.getElementById('updateDeliveryForm').addEventListener('submit', function(e) {
    const deliveryStatus = document.getElementById('delivery_status').value;
    const actualDate = document.getElementById('actual_date').value;
    
    if (!deliveryStatus) {
        e.preventDefault();
        alert('Please select a delivery status.');
        return false;
    }
    
    if (deliveryStatus === 'Delivered' && !actualDate) {
        e.preventDefault();
        alert('Please provide the actual delivery date.');
        return false;
    }
    
    // Confirmation
    if (!confirm('Are you sure you want to update the delivery status to "' + deliveryStatus + '"?')) {
        e.preventDefault();
        return false;
    }
});

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Trigger change event to show/hide fields based on current selection
    document.getElementById('delivery_status').dispatchEvent(new Event('change'));
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Update Delivery Status - ConstructLink™';
$pageHeader = 'Update Delivery Status for PO #' . htmlspecialchars($procurementOrder['po_number']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'View Order', 'url' => '?route=procurement-orders/view&id=' . $procurementOrder['id']],
    ['title' => 'Update Delivery', 'url' => '?route=procurement-orders/update-delivery&id=' . $procurementOrder['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
