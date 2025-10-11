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
        <i class="bi bi-info-circle me-2"></i>Legacy Item Verification
    </h6>
    <p class="mb-0">
        Review and verify legacy items created by Warehousemen. Check that the items physically exist on-site and match the recorded information.
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
    <div class="alert alert-danger mt-4">You do not have permission to verify legacy items.</div>
<?php else: ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Verification</h6>
                        <h3 class="mb-0"><?= $workflowStats['pending_verification'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Authorization</h6>
                        <h3 class="mb-0"><?= $workflowStats['pending_authorization'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-hourglass-split fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Approved Legacy</h6>
                        <h3 class="mb-0"><?= $workflowStats['approved_legacy'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Legacy Items</h6>
                        <h3 class="mb-0"><?= $workflowStats['total_legacy'] ?? 0 ?></h3>
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
                        Select all items on this page
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

<!-- Pending Items Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="bi bi-clock me-1"></i>Items Pending Verification
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
                        <th>Item Name</th>
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
                    <?php if (!empty($pendingAssets)): ?>
                        <?php foreach ($pendingAssets as $asset): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input asset-checkbox"
                                           value="<?= $asset['id'] ?>" onchange="updateSelectedAssets()">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($asset['name']) ?></strong><br>
                                    <small class="text-muted">REF: <?= htmlspecialchars($asset['ref']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($asset['brand'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($asset['maker_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($asset['category_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($asset['sub_location'])): ?>
                                        <?= htmlspecialchars($asset['sub_location']) ?>
                                    <?php elseif (!empty($asset['location'])): ?>
                                        <?= htmlspecialchars($asset['location']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($asset['quantity'] ?? 1) ?> <?= htmlspecialchars($asset['unit'] ?? 'pc') ?></td>
                                <td><?= htmlspecialchars($asset['made_by_username'] ?? 'Unknown') ?></td>
                                <td><?= !empty($asset['created_at']) ? date('M d, Y', strtotime($asset['created_at'])) : 'N/A' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>"
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success"
                                                onclick="verifyAsset(<?= $asset['id'] ?>)" title="Verify">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-2">No pending items</h5>
                                <p class="text-muted">All legacy items have been verified!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle me-2"></i>Verify Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="assetDetails">
                    <!-- Item details will be loaded here -->
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
                                          placeholder="Add any notes about the item condition, location verification, or concerns..."></textarea>
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
                    <i class="bi bi-check-circle me-1"></i>Verify Item
                </button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
let pendingAssets = [];
let selectedAssets = [];

// CSRF Token
const CSRFTokenValue = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Initialize dashboard on load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

// Initialize event listeners
function initializeEventListeners() {
    
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
                <h6 class="text-primary">Item Information</h6>
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
    // AJAX call to verify asset
    const formData = new URLSearchParams();
    formData.append('asset_id', assetId);
    if (notes) formData.append('verification_notes', notes);
    if (actualQuantity) formData.append('actual_quantity', actualQuantity);
    if (actualCondition) formData.append('actual_condition', actualCondition);

    fetch('?route=assets/verify-asset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('verificationModal')).hide();

        if (data.success) {
            showAlert('success', data.message || 'Item verified successfully!');
            // Reload page to refresh data
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Failed to verify item');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while verifying the item');
    });
}

// Show batch verification modal with review table
function bulkVerifyAssets() {
    if (selectedAssets.length === 0) {
        showAlert('warning', 'Please select items to verify');
        return;
    }

    // Build review table
    const selectedRows = Array.from(document.querySelectorAll('.asset-checkbox:checked')).map(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        return {
            id: checkbox.value,
            name: cells[1]?.textContent.trim() || '',
            brand: cells[2]?.textContent.trim() || '',
            manufacturer: cells[3]?.textContent.trim() || '',
            category: cells[4]?.textContent.trim() || '',
            location: cells[5]?.textContent.trim() || '',
            quantity: cells[6]?.textContent.trim() || '',
            createdBy: cells[7]?.textContent.trim() || '',
            createdDate: cells[8]?.textContent.trim() || ''
        };
    });

    const tableHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Item Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    ${selectedRows.map(item => `
                        <tr>
                            <td><strong>${item.name}</strong></td>
                            <td>${item.brand}</td>
                            <td>${item.category}</td>
                            <td>${item.location}</td>
                            <td>${item.quantity}</td>
                            <td>${item.createdBy}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Total items to verify:</strong> ${selectedAssets.length}
        </div>
    `;

    // Show confirmation modal with review table
    const modalHTML = `
        <div class="modal fade" id="batchVerificationModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>Batch Verification Review
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="lead">Please review the following items before verification:</p>
                        ${tableHTML}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="confirmBatchVerification()">
                            <i class="bi bi-check-circle me-1"></i>Verify All ${selectedAssets.length} Items
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('batchVerificationModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('batchVerificationModal'));
    modal.show();
}

// Confirm batch verification
function confirmBatchVerification() {
    const formData = new URLSearchParams();

    // Add each asset ID as an array element
    selectedAssets.forEach(id => {
        formData.append('asset_ids[]', id);
    });

    fetch('?route=assets/batch-verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('batchVerificationModal'));
        modal.hide();

        if (data.success) {
            showAlert('success', data.message || `${selectedAssets.length} items verified successfully!`);
            // Reload page to refresh data
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Failed to verify items');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred while verifying items');
    });
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
    window.location.reload();
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Item Verification Dashboard - ConstructLink™';
$pageHeader = 'Item Verification Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'Verification Dashboard', 'url' => '?route=assets/verification-dashboard']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>