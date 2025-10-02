<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

// Helper function for status badges
function getAssetStatusBadge($status) {
    $badges = [
        'available' => 'bg-success',
        'in_use' => 'bg-primary',
        'borrowed' => 'bg-info',
        'under_maintenance' => 'bg-warning',
        'retired' => 'bg-secondary',
        'disposed' => 'bg-dark'
    ];
    return $badges[$status] ?? 'bg-secondary';
}

function formatAssetStatus($status) {
    return ucfirst(str_replace('_', ' ', $status));
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-box-seam me-2"></i>
        Asset: <?= htmlspecialchars($asset['ref'] ?? 'Unknown') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=assets" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Assets
            </a>
        </div>
        
        <!-- Action Buttons based on status and permissions -->
        <?php if ($asset['status'] === 'available'): ?>
            <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                <div class="btn-group me-2">
                    <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" class="btn btn-success">
                        <i class="bi bi-box-arrow-right me-1"></i>Withdraw Asset
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($auth->hasRole(['System Admin', 'Asset Director', 'Procurement Officer'])): ?>
            <div class="btn-group me-2">
                <a href="?route=assets/edit&id=<?= $asset['id'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Edit Asset
                </a>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <button type="button" class="btn btn-outline-info" onclick="printAssetDetails()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <?php if (!empty($asset['qr_code'])): ?>
                <button type="button" class="btn btn-outline-primary" onclick="showQRCode()">
                    <i class="bi bi-qr-code me-1"></i>QR Code
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] === 'asset_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Asset created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Asset Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Reference:</dt>
                            <dd class="col-sm-7">
                                <strong><?= htmlspecialchars($asset['ref']) ?></strong>
                                <?php if (!empty($asset['qr_code'])): ?>
                                    <i class="bi bi-qr-code text-primary ms-1" title="QR Code Available"></i>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge <?= getAssetStatusBadge($asset['status']) ?>">
                                    <?= formatAssetStatus($asset['status']) ?>
                                </span>
                            </dd>
                            
                            <dt class="col-sm-5">Category:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Project:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-light text-dark">
                                    <?= htmlspecialchars($asset['project_name'] ?? 'N/A') ?>
                                </span>
                                <?php if (!empty($asset['project_location'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($asset['project_location']) ?></small>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-5">Acquired Date:</dt>
                            <dd class="col-sm-7"><?= date('M j, Y', strtotime($asset['acquired_date'])) ?></dd>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-5">Manufacturer:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['maker_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Vendor:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['vendor_name'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Model:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['model'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Serial Number:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['serial_number'] ?? 'N/A') ?></dd>
                            
                            <dt class="col-sm-5">Current Location:</dt>
                            <dd class="col-sm-7"><?= htmlspecialchars($asset['location'] ?? 'N/A') ?></dd>
                        </dl>
                    </div>
                </div>
                
                <?php if (!empty($asset['description'])): ?>
                    <div class="mt-3">
                        <h6>Description:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($asset['specifications'])): ?>
                    <div class="mt-3">
                        <h6>Technical Specifications:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['specifications'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($asset['condition_notes'])): ?>
                    <div class="mt-3">
                        <h6>Condition Notes:</h6>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($asset['condition_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Financial Information -->
        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director']) && ($asset['acquisition_cost'] || $asset['unit_cost'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-currency-dollar me-2"></i>Financial Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h5 class="text-primary"><?= formatCurrency($asset['acquisition_cost'] ?? 0) ?></h5>
                            <small class="text-muted">Acquisition Cost</small>
                        </div>
                    </div>
                    <?php if ($asset['unit_cost'] && $asset['unit_cost'] != $asset['acquisition_cost']): ?>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h5 class="text-info"><?= formatCurrency($asset['unit_cost']) ?></h5>
                            <small class="text-muted">Unit Cost</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($asset['warranty_expiry']): ?>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6 class="<?= strtotime($asset['warranty_expiry']) < time() ? 'text-danger' : 'text-success' ?>">
                                <?= date('M j, Y', strtotime($asset['warranty_expiry'])) ?>
                            </h6>
                            <small class="text-muted">Warranty Expiry</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($asset['is_client_supplied']): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>This asset was supplied by the client.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Procurement Information -->
        <?php if (!empty($asset['procurement_order_id']) || !empty($asset['po_number'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-cart me-2"></i>Procurement Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <?php if (!empty($asset['po_number'])): ?>
                        <dt class="col-sm-3">Purchase Order:</dt>
                        <dd class="col-sm-9">
                            <a href="?route=procurement-orders/view&id=<?= $asset['procurement_order_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($asset['po_number']) ?>
                            </a>
                            <?php if (!empty($asset['procurement_title'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($asset['procurement_title']) ?></small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($asset['procurement_item_name'])): ?>
                        <dt class="col-sm-3">Procurement Item:</dt>
                        <dd class="col-sm-9">
                            <?= htmlspecialchars($asset['procurement_item_name']) ?>
                            <?php if (!empty($asset['procurement_item_brand'])): ?>
                                <br><small class="text-muted">Brand: <?= htmlspecialchars($asset['procurement_item_brand']) ?></small>
                            <?php endif; ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($asset['procurement_order_status'])): ?>
                        <dt class="col-sm-3">Order Status:</dt>
                        <dd class="col-sm-9">
                            <?php
                            $statusClasses = [
                                'draft' => 'bg-secondary',
                                'pending' => 'bg-warning',
                                'approved' => 'bg-success',
                                'received' => 'bg-info',
                                'rejected' => 'bg-danger'
                            ];
                            $statusClass = $statusClasses[$asset['procurement_order_status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= ucfirst($asset['procurement_order_status']) ?>
                            </span>
                        </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Asset History -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Asset History
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($history)): ?>
                    <div class="timeline">
                        <?php foreach ($history as $entry): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="bi bi-<?= getHistoryIcon($entry['type']) ?> text-primary"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title"><?= getHistoryTitle($entry['type']) ?></h6>
                                    <p class="timeline-text"><?= htmlspecialchars($entry['description']) ?></p>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($entry['date'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-clock-history display-4 text-muted"></i>
                        <p class="text-muted mt-2">No history records found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <?php if ($asset['status'] === 'available'): ?>
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                        <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-box-arrow-right me-1"></i>Withdraw Asset
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Project Manager'])): ?>
                        <a href="?route=transfers/create&asset_id=<?= $asset['id'] ?>" class="btn btn-info w-100 mb-2">
                            <i class="bi bi-arrow-left-right me-1"></i>Transfer Asset
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Warehouseman', 'Site Inventory Clerk'])): ?>
                        <a href="?route=borrowed-tools/create&asset_id=<?= $asset['id'] ?>" class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-person-check me-1"></i>Lend Asset
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                    <a href="?route=maintenance/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-tools me-1"></i>Schedule Maintenance
                    </a>
                    
                    <a href="?route=incidents/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-danger w-100 mb-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>Report Incident
                    </a>
                <?php endif; ?>
                
                <button type="button" class="btn btn-outline-secondary w-100" onclick="updateAssetStatus()">
                    <i class="bi bi-arrow-repeat me-1"></i>Update Status
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-activity me-2"></i>Recent Activity
                </h6>
            </div>
            <div class="card-body">
                <!-- Withdrawals -->
                <?php if (!empty($withdrawals)): ?>
                    <h6 class="text-primary">Recent Withdrawals</h6>
                    <?php foreach (array_slice($withdrawals, 0, 3) as $withdrawal): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="fw-medium"><?= htmlspecialchars($withdrawal['receiver_name'] ?? 'Unknown') ?></small>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($withdrawal['purpose'] ?? '') ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= getStatusBadgeClass($withdrawal['status'] ?? 'unknown') ?>">
                                    <?= getStatusLabel($withdrawal['status'] ?? 'unknown') ?>
                                </span>
                                <br>
                                <small class="text-muted"><?= formatDate($withdrawal['created_at'] ?? '') ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($withdrawals) > 3): ?>
                        <small class="text-muted">And <?= count($withdrawals) - 3 ?> more...</small>
                    <?php endif; ?>
                    <hr>
                <?php endif; ?>
                
                <!-- Maintenance -->
                <?php if (!empty($maintenance)): ?>
                    <h6 class="text-warning">Maintenance Records</h6>
                    <?php foreach (array_slice($maintenance, 0, 2) as $maint): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="fw-medium"><?= ucfirst($maint['type']) ?> Maintenance</small>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($maint['description']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning">
                                    <?= ucfirst($maint['status']) ?>
                                </span>
                                <br>
                                <small class="text-muted"><?= formatDate($maint['scheduled_date']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                <?php endif; ?>
                
                <!-- Incidents -->
                <?php if (!empty($incidents)): ?>
                    <h6 class="text-danger">Incident Reports</h6>
                    <?php foreach (array_slice($incidents, 0, 2) as $incident): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <small class="fw-medium"><?= ucfirst($incident['type']) ?></small>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($incident['description']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger">
                                    <?= ucfirst($incident['status']) ?>
                                </span>
                                <br>
                                <small class="text-muted"><?= formatDate($incident['date_reported']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (empty($withdrawals) && empty($maintenance) && empty($incidents)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-activity display-4 text-muted"></i>
                        <p class="text-muted mt-2">No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Asset Statistics -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-graph-up me-2"></i>Asset Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-value text-success"><?= count($withdrawals ?? []) ?></div>
                            <div class="stat-label">Withdrawals</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-value text-info"><?= count($transfers ?? []) ?></div>
                            <div class="stat-label">Transfers</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-value text-warning"><?= count($maintenance ?? []) ?></div>
                            <div class="stat-label">Maintenance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<?php if (!empty($asset['qr_code'])): ?>
<div class="modal fade" id="qrCodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asset QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeContainer">
                    <!-- QR Code will be generated here -->
                    <div class="border p-4 d-inline-block">
                        <div style="width: 200px; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-qr-code display-1 text-muted"></i>
                        </div>
                    </div>
                </div>
                <p class="mt-3 mb-0">
                    <strong><?= htmlspecialchars($asset['ref']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($asset['name']) ?></small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="printQRCode()">
                    <i class="bi bi-printer me-1"></i>Print QR Code
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Asset Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusUpdateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-select" id="newStatus" name="status" required>
                            <option value="">Select Status</option>
                            <option value="available" <?= $asset['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="in_use" <?= $asset['status'] === 'in_use' ? 'selected' : '' ?>>In Use</option>
                            <option value="under_maintenance" <?= $asset['status'] === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                            <option value="retired" <?= $asset['status'] === 'retired' ? 'selected' : '' ?>>Retired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="3" 
                                  placeholder="Reason for status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show QR Code Modal
function showQRCode() {
    const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
    modal.show();
}

// Print Asset Details
function printAssetDetails() {
    window.print();
}

// Print QR Code
function printQRCode() {
    const qrContent = document.getElementById('qrCodeContainer').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head><title>Asset QR Code - <?= htmlspecialchars($asset['ref']) ?></title></head>
            <body style="text-align: center; padding: 20px;">
                ${qrContent}
                <h3><?= htmlspecialchars($asset['ref']) ?></h3>
                <p><?= htmlspecialchars($asset['name']) ?></p>
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Update Asset Status
function updateAssetStatus() {
    const modal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
    modal.show();
}

// Handle status update form submission
document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('asset_id', <?= $asset['id'] ?>);
    formData.append('status', document.getElementById('newStatus').value);
    formData.append('notes', document.getElementById('statusNotes').value);
    
    fetch('?route=assets/updateStatus', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
    });
});
</script>

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
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 2px solid #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 0.85rem;
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
    .btn-toolbar, .card-header, .timeline-marker {
        display: none !important;
    }
}
</style>

<?php
// Helper functions for history display
function getHistoryIcon($type) {
    $icons = [
        'withdrawal' => 'box-arrow-right',
        'transfer' => 'arrow-left-right',
        'maintenance' => 'tools',
        'incident' => 'exclamation-triangle',
        'status_change' => 'arrow-repeat'
    ];
    return $icons[$type] ?? 'clock';
}

function getHistoryTitle($type) {
    $titles = [
        'withdrawal' => 'Asset Withdrawal',
        'transfer' => 'Asset Transfer',
        'maintenance' => 'Maintenance Activity',
        'incident' => 'Incident Report',
        'status_change' => 'Status Change'
    ];
    return $titles[$type] ?? 'Activity';
}

// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
