<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Legacy Asset Verification
    </h6>
    <p class="mb-0">
        Review and verify legacy assets created by Warehousemen. Check that the assets physically exist on-site and match the recorded information.
    </p>
</div>

<!-- Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!in_array($user['role_name'], $roleConfig['assets/legacy-verify'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to verify legacy assets.</div>
<?php else: ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Verification</h6>
                        <h3 class="mb-0" id="pendingCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Verified Today</h6>
                        <h3 class="mb-0" id="verifiedTodayCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">This Week</h6>
                        <h3 class="mb-0" id="weekCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-week fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Verified</h6>
                        <h3 class="mb-0" id="totalCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clipboard-check fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-list-check me-1"></i>Bulk Actions
        </h6>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">
                        Select all assets on this page
                    </label>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-success" id="bulkVerifyBtn" disabled>
                    <i class="bi bi-check-circle me-1"></i>Verify Selected
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pending Assets Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="bi bi-clock me-1"></i>Assets Pending Verification
            </h6>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="locationFilter" style="width: auto;">
                    <option value="">All Locations</option>
                    <option value="Warehouse">Warehouse</option>
                    <option value="Tool Room">Tool Room</option>
                    <option value="Office">Office</option>
                    <option value="Site Area A">Site Area A</option>
                    <option value="Site Area B">Site Area B</option>
                    <option value="Site Area C">Site Area C</option>
                    <option value="Equipment Yard">Equipment Yard</option>
                    <option value="Storage">Storage</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="pendingAssetsTable">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="form-check-input" id="headerSelectAll">
                        </th>
                        <th>Asset Name</th>
                        <th>Brand</th>
                        <th>Manufacturer</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingAssetsBody">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>
        <div id="loadingMessage" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading pending assets...</p>
        </div>
        <div id="noDataMessage" class="text-center py-4" style="display: none;">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <h5 class="text-muted mt-2">No pending assets</h5>
            <p class="text-muted">All legacy assets have been verified!</p>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i>Verify Asset
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="assetDetails">
                    <!-- Asset details will be loaded here -->
                </div>
                <form id="verificationForm">
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Verification Notes</h6>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="verificationNotes" class="form-label">Verification Comments</label>
                                <textarea class="form-control" id="verificationNotes" name="verification_notes" rows="3" 
                                          placeholder="Add any notes about the asset condition, location verification, or concerns..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="actualQuantity" class="form-label">Actual Quantity Found</label>
                                <input type="number" class="form-control" id="actualQuantity" name="actual_quantity" 
                                       min="0" max="9999" placeholder="Enter actual quantity">
                                <div class="form-text">Leave blank if quantity matches record</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="actualCondition" class="form-label">Actual Condition</label>
                                <select class="form-select" id="actualCondition" name="actual_condition">
                                    <option value="">Same as recorded</option>
                                    <option value="Excellent - Like new">Excellent - Like new</option>
                                    <option value="Good - Working well">Good - Working well</option>
                                    <option value="Fair - Some wear">Fair - Some wear</option>
                                    <option value="Poor - Needs repair">Poor - Needs repair</option>
                                    <option value="Needs maintenance">Needs maintenance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmVerifyBtn">
                    <i class="bi bi-check-circle me-1"></i>Verify Asset
                </button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
let pendingAssets = [];
let selectedAssets = [];

// Initialize dashboard on load
document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    loadPendingAssets();
    initializeEventListeners();
});

// Load dashboard statistics
function loadStatistics() {
    // This would be AJAX calls in real implementation
    document.getElementById('pendingCount').textContent = '0';
    document.getElementById('verifiedTodayCount').textContent = '0';
    document.getElementById('weekCount').textContent = '0';
    document.getElementById('totalCount').textContent = '0';
}

// Load pending assets
function loadPendingAssets() {
    const locationFilter = document.getElementById('locationFilter').value;
    
    // Show loading
    document.getElementById('loadingMessage').style.display = 'block';
    document.getElementById('noDataMessage').style.display = 'none';
    
    // This would be an AJAX call in real implementation
    setTimeout(() => {
        document.getElementById('loadingMessage').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'block';
        
        // Update pending count
        document.getElementById('pendingCount').textContent = pendingAssets.length;
    }, 1000);
}

// Initialize event listeners
function initializeEventListeners() {
    // Location filter
    document.getElementById('locationFilter').addEventListener('change', loadPendingAssets);
    
    // Select all functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.asset-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedAssets();
    });
    
    document.getElementById('headerSelectAll').addEventListener('change', function() {
        document.getElementById('selectAll').checked = this.checked;
        document.getElementById('selectAll').dispatchEvent(new Event('change'));
    });
    
    // Bulk verify button
    document.getElementById('bulkVerifyBtn').addEventListener('click', function() {
        if (selectedAssets.length > 0) {
            if (confirm(`Verify ${selectedAssets.length} selected assets?`)) {
                bulkVerifyAssets();
            }
        }
    });
    
    // Verification form submission
    document.getElementById('confirmVerifyBtn').addEventListener('click', function() {
        const assetId = this.dataset.assetId;
        const notes = document.getElementById('verificationNotes').value;
        const actualQuantity = document.getElementById('actualQuantity').value;
        const actualCondition = document.getElementById('actualCondition').value;
        
        verifyAsset(assetId, notes, actualQuantity, actualCondition);
    });
}

// Update selected assets array
function updateSelectedAssets() {
    selectedAssets = [];
    const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
    checkboxes.forEach(cb => selectedAssets.push(cb.value));
    
    document.getElementById('bulkVerifyBtn').disabled = selectedAssets.length === 0;
}

// Show verification modal
function showVerificationModal(assetId, assetName, category, location, quantity, condition, createdBy) {
    const assetDetails = document.getElementById('assetDetails');
    assetDetails.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Asset Information</h6>
                <p><strong>Name:</strong> ${assetName}</p>
                <p><strong>Category:</strong> ${category}</p>
                <p><strong>Location:</strong> ${location}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Current Details</h6>
                <p><strong>Quantity:</strong> ${quantity}</p>
                <p><strong>Condition:</strong> ${condition || 'Not specified'}</p>
                <p><strong>Created by:</strong> ${createdBy}</p>
            </div>
        </div>
    `;
    
    // Reset form
    document.getElementById('verificationForm').reset();
    document.getElementById('confirmVerifyBtn').dataset.assetId = assetId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
    modal.show();
}

// Verify single asset
function verifyAsset(assetId, notes, actualQuantity, actualCondition) {
    // This would be an AJAX call in real implementation
    console.log('Verifying asset:', assetId, notes, actualQuantity, actualCondition);
    
    // Close modal and show success
    bootstrap.Modal.getInstance(document.getElementById('verificationModal')).hide();
    
    // Show success message
    showAlert('success', 'Asset verified successfully!');
    
    // Refresh data
    loadStatistics();
    loadPendingAssets();
}

// Bulk verify assets
function bulkVerifyAssets() {
    // This would be an AJAX call in real implementation
    console.log('Bulk verifying assets:', selectedAssets);
    
    // Show success message
    showAlert('success', `${selectedAssets.length} assets verified successfully!`);
    
    // Clear selections
    selectedAssets = [];
    document.getElementById('selectAll').checked = false;
    document.getElementById('headerSelectAll').checked = false;
    document.getElementById('bulkVerifyBtn').disabled = true;
    
    // Refresh data
    loadStatistics();
    loadPendingAssets();
}

// Utility function to show alerts
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after page header
    const pageHeader = document.querySelector('.border-bottom');
    pageHeader.parentNode.insertBefore(alertDiv, pageHeader.nextSibling);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Refresh table data
function refreshTable() {
    loadPendingAssets();
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Asset Verification Dashboard - ConstructLink™';
$pageHeader = 'Asset Verification Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets'],
    ['title' => 'Verification Dashboard', 'url' => '?route=assets/verification-dashboard']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>