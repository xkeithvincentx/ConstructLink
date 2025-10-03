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
        <i class="bi bi-info-circle me-2"></i>Legacy Asset Authorization
    </h6>
    <p class="mb-0">
        Authorize verified legacy assets as official project property. These assets have been verified by the Site Inventory Clerk and are ready for final approval.
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

<?php if (!in_array($user['role_name'], $roleConfig['assets/legacy-authorize'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to authorize legacy assets.</div>
<?php else: ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Authorization</h6>
                        <h3 class="mb-0" id="pendingCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-exclamation fs-2"></i>
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
                        <h6 class="card-title">Authorized Today</h6>
                        <h3 class="mb-0" id="authorizedTodayCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-check fs-2"></i>
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
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Assets</h6>
                        <h3 class="mb-0" id="totalCount">-</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-collection fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-lightning me-1"></i>Quick Actions
        </h6>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">
                        Select all assets on this page
                    </label>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success me-2" id="bulkAuthorizeBtn" disabled>
                    <i class="bi bi-shield-check me-1"></i>Authorize Selected
                </button>
                <button type="button" class="btn btn-outline-info" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Verified Assets Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="bi bi-shield-exclamation me-1"></i>Assets Ready for Authorization
            </h6>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="categoryFilter" style="width: auto;">
                    <option value="">All Categories</option>
                    <!-- Categories will be populated via AJAX -->
                </select>
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
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="pendingAuthorizationTable">
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
                        <th>Verified By</th>
                        <th>Verification Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingAuthorizationBody">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>
        <div id="loadingMessage" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading assets ready for authorization...</p>
        </div>
        <div id="noDataMessage" class="text-center py-4" style="display: none;">
            <i class="bi bi-shield-check text-muted" style="font-size: 3rem;"></i>
            <h5 class="text-muted mt-2">No assets pending authorization</h5>
            <p class="text-muted">All verified assets have been authorized!</p>
        </div>
    </div>
</div>

<!-- Authorization Modal -->
<div class="modal fade" id="authorizationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-check me-2"></i>Authorize Asset
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="assetDetails">
                    <!-- Asset details will be loaded here -->
                </div>
                <form id="authorizationForm">
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Authorization Review</h6>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="authorizationNotes" class="form-label">Authorization Comments</label>
                                <textarea class="form-control" id="authorizationNotes" name="authorization_notes" rows="3" 
                                          placeholder="Add any comments about authorizing this asset as project property..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="finalLocation" class="form-label">Final Location Assignment</label>
                                <select class="form-select" id="finalLocation" name="final_location">
                                    <option value="">Keep current location</option>
                                    <option value="Warehouse">Warehouse</option>
                                    <option value="Tool Room">Tool Room</option>
                                    <option value="Office">Office</option>
                                    <option value="Site Area A">Site Area A</option>
                                    <option value="Site Area B">Site Area B</option>
                                    <option value="Site Area C">Site Area C</option>
                                    <option value="Equipment Yard">Equipment Yard</option>
                                    <option value="Storage">Storage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assetTagPrefix" class="form-label">Asset Tag Prefix (Optional)</label>
                                <input type="text" class="form-control" id="assetTagPrefix" name="asset_tag_prefix" 
                                       placeholder="e.g., PRJ-2024-" maxlength="20">
                                <div class="form-text">Custom prefix for asset reference number</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAuthorizeBtn">
                    <i class="bi bi-shield-check me-1"></i>Authorize Asset
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
    loadCategories();
    initializeEventListeners();
});

// Load dashboard statistics
function loadStatistics() {
    // This would be AJAX calls in real implementation
    document.getElementById('pendingCount').textContent = '0';
    document.getElementById('authorizedTodayCount').textContent = '0';
    document.getElementById('weekCount').textContent = '0';
    document.getElementById('totalCount').textContent = '0';
}

// Load categories for filter
function loadCategories() {
    // This would be an AJAX call in real implementation
    const categoryFilter = document.getElementById('categoryFilter');
    // Add sample categories
    const sampleCategories = ['Tools', 'Equipment', 'Furniture', 'Vehicles'];
    sampleCategories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
}

// Load pending authorization assets
function loadPendingAssets() {
    const categoryFilter = document.getElementById('categoryFilter').value;
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
    // Filter change handlers
    document.getElementById('categoryFilter').addEventListener('change', loadPendingAssets);
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
    
    // Bulk authorize button
    document.getElementById('bulkAuthorizeBtn').addEventListener('click', function() {
        if (selectedAssets.length > 0) {
            if (confirm(`Authorize ${selectedAssets.length} selected assets as project property?`)) {
                bulkAuthorizeAssets();
            }
        }
    });
    
    // Authorization form submission
    document.getElementById('confirmAuthorizeBtn').addEventListener('click', function() {
        const assetId = this.dataset.assetId;
        const notes = document.getElementById('authorizationNotes').value;
        const finalLocation = document.getElementById('finalLocation').value;
        const assetTagPrefix = document.getElementById('assetTagPrefix').value;
        
        authorizeAsset(assetId, notes, finalLocation, assetTagPrefix);
    });
}

// Update selected assets array
function updateSelectedAssets() {
    selectedAssets = [];
    const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
    checkboxes.forEach(cb => selectedAssets.push(cb.value));
    
    document.getElementById('bulkAuthorizeBtn').disabled = selectedAssets.length === 0;
}

// Show authorization modal
function showAuthorizationModal(assetId, assetName, category, location, quantity, condition, createdBy, verifiedBy, verificationDate) {
    const assetDetails = document.getElementById('assetDetails');
    assetDetails.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Asset Information</h6>
                <p><strong>Name:</strong> ${assetName}</p>
                <p><strong>Category:</strong> ${category}</p>
                <p><strong>Location:</strong> ${location}</p>
                <p><strong>Quantity:</strong> ${quantity}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-success">Verification Details</h6>
                <p><strong>Created by:</strong> ${createdBy}</p>
                <p><strong>Verified by:</strong> ${verifiedBy}</p>
                <p><strong>Verification Date:</strong> ${verificationDate}</p>
                <p><strong>Condition:</strong> ${condition || 'Not specified'}</p>
            </div>
        </div>
    `;
    
    // Reset form
    document.getElementById('authorizationForm').reset();
    document.getElementById('confirmAuthorizeBtn').dataset.assetId = assetId;
    
    // Set current location as default
    document.getElementById('finalLocation').value = location;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('authorizationModal'));
    modal.show();
}

// Authorize single asset
function authorizeAsset(assetId, notes, finalLocation, assetTagPrefix) {
    // This would be an AJAX call in real implementation
    console.log('Authorizing asset:', assetId, notes, finalLocation, assetTagPrefix);
    
    // Close modal and show success
    bootstrap.Modal.getInstance(document.getElementById('authorizationModal')).hide();
    
    // Show success message
    showAlert('success', 'Asset authorized successfully!');
    
    // Refresh data
    loadStatistics();
    loadPendingAssets();
}

// Bulk authorize assets
function bulkAuthorizeAssets() {
    // This would be an AJAX call in real implementation
    console.log('Bulk authorizing assets:', selectedAssets);
    
    // Show success message
    showAlert('success', `${selectedAssets.length} assets authorized successfully!`);
    
    // Clear selections
    selectedAssets = [];
    document.getElementById('selectAll').checked = false;
    document.getElementById('headerSelectAll').checked = false;
    document.getElementById('bulkAuthorizeBtn').disabled = true;
    
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
$pageTitle = 'Asset Authorization Dashboard - ConstructLink™';
$pageHeader = 'Asset Authorization Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets'],
    ['title' => 'Authorization Dashboard', 'url' => '?route=assets/authorization-dashboard']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>