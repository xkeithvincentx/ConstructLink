<?php
/**
 * ConstructLink™ Schedule Delivery View
 * Schedule delivery for approved procurement orders
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-calendar-plus me-2"></i>
        Schedule Delivery
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order
        </a>
    </div>
</div>

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
        <!-- Schedule Delivery Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-truck me-2"></i>Schedule Delivery for PO #<?= htmlspecialchars($procurementOrder['po_number']) ?>
                </h6>
            </div>
            <div class="card-body">
                <?php if (!in_array($userRole, $roleConfig['procurement-orders/schedule-delivery'] ?? [])): ?>
                    <div class="alert alert-danger mt-4">You do not have permission to schedule delivery for this procurement order.</div>
                <?php else: ?>
                <form method="POST" action="?route=procurement-orders/schedule-delivery&id=<?= $procurementOrder['id'] ?>" id="scheduleDeliveryForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_date" class="form-label">Scheduled Delivery Date <span class="text-danger">*</span></label>
                            <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" 
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            <div class="form-text">
                                Select the expected delivery date.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="delivery_method" class="form-label">Delivery Method <span class="text-danger">*</span></label>
                            <select name="delivery_method" id="delivery_method" class="form-select" required>
                                <option value="">Select Delivery Method</option>
                                <?php foreach (getDeliveryMethodOptions($procurementOrder['id']) as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="delivery_location" class="form-label">Delivery Location <span class="text-danger">*</span></label>
                        <select name="delivery_location" id="delivery_location" class="form-select" required>
                            <option value="">Select Delivery Location</option>
                            <?php foreach (getDeliveryLocationOptions($procurementOrder['id']) as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tracking_number" class="form-label">Tracking Number</label>
                        <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                               placeholder="Enter tracking number if available">
                        <div class="form-text">
                            Optional: Enter tracking number for shipment monitoring.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="delivery_notes" class="form-label">Delivery Notes</label>
                        <textarea name="delivery_notes" id="delivery_notes" class="form-control" rows="4" 
                                  placeholder="Enter any special delivery instructions, contact information, or notes..."></textarea>
                        <div class="form-text">
                            Include any special instructions, contact person details, or access requirements.
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calendar-check me-1"></i>Schedule Delivery
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Order Summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Order Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">PO Number:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['po_number']) ?></dd>
                    
                    <dt class="col-sm-5">Vendor:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Total Amount:</dt>
                    <dd class="col-sm-7">₱<?= number_format($procurementOrder['net_total'], 2) ?></dd>
                    
                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success"><?= htmlspecialchars($procurementOrder['status']) ?></span>
                    </dd>
                    
                    <?php if (!empty($procurementOrder['date_needed'])): ?>
                    <dt class="col-sm-5">Date Needed:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($procurementOrder['date_needed'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Delivery Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Delivery Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Delivery Methods:</h6>
                <ul class="small">
                    <li><strong>Pickup:</strong> Items will be collected from vendor</li>
                    <li><strong>Direct Delivery:</strong> Direct delivery to specified location</li>
                    <li><strong>Batch Delivery:</strong> Combined with other orders</li>
                    <li><strong>Airfreight:</strong> Air transportation for urgent items</li>
                    <li><strong>Bus Cargo:</strong> Ground transportation via bus</li>
                    <li><strong>Courier:</strong> Express courier service</li>
                </ul>
                
                <h6 class="mt-3">Important Notes:</h6>
                <ul class="small">
                    <li>Ensure delivery location is accessible</li>
                    <li>Provide contact person details</li>
                    <li>Consider project timeline requirements</li>
                    <li>Include any special handling instructions</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('scheduleDeliveryForm').addEventListener('submit', function(e) {
    const scheduledDate = document.getElementById('scheduled_date').value;
    const deliveryMethod = document.getElementById('delivery_method').value;
    const deliveryLocation = document.getElementById('delivery_location').value;
    
    if (!scheduledDate || !deliveryMethod || !deliveryLocation) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    // Check if date is in the future
    const selectedDate = new Date(scheduledDate);
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    
    if (selectedDate < tomorrow) {
        e.preventDefault();
        alert('Scheduled delivery date must be at least tomorrow.');
        return false;
    }
    
    // Confirmation
    if (!confirm('Are you sure you want to schedule this delivery?')) {
        e.preventDefault();
        return false;
    }
});

// Auto-populate delivery location based on project
document.addEventListener('DOMContentLoaded', function() {
    const deliveryLocation = document.getElementById('delivery_location');
    // Default to Project Site for most orders
    if (deliveryLocation.value === '') {
        deliveryLocation.value = 'Project Site';
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Schedule Delivery - ConstructLink™';
$pageHeader = 'Schedule Delivery for PO #' . htmlspecialchars($procurementOrder['po_number']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'View Order', 'url' => '?route=procurement-orders/view&id=' . $procurementOrder['id']],
    ['title' => 'Schedule Delivery', 'url' => '?route=procurement-orders/schedule-delivery&id=' . $procurementOrder['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
