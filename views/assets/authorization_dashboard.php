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
        <i class="bi bi-info-circle me-2"></i>Legacy Item Authorization
    </h6>
    <p class="mb-0">
        Authorize verified legacy items as official project property. These items have been verified by the Site Inventory Clerk and are ready for final approval.
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
    <div class="alert alert-danger mt-4">You do not have permission to authorize legacy items.</div>
<?php else: ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Pending Authorization</h6>
                        <h3 class="mb-0"><?= $workflowStats['pending_authorization'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-exclamation fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Approved Legacy</h6>
                        <h3 class="mb-0"><?= $workflowStats['approved_legacy'] ?? 0 ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-shield-check fs-2"></i>
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
                        <h6 class="card-title">Total Legacy Items</h6>
                        <h3 class="mb-0"><?= $workflowStats['total_legacy'] ?? 0 ?></h3>
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
                        Select all items on this page
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

<!-- Verified Items Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="bi bi-shield-exclamation me-1"></i>Items Ready for Authorization
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
                        <th>Item Name</th>
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
                                <td><?= htmlspecialchars($asset['created_by_username'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($asset['verified_by_username'] ?? 'Unknown') ?></td>
                                <td><?= !empty($asset['verified_at']) ? date('M d, Y', strtotime($asset['verified_at'])) : 'N/A' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=assets/view&id=<?= $asset['id'] ?>"
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success"
                                                onclick="authorizeAsset(<?= $asset['id'] ?>)" title="Authorize">
                                            <i class="bi bi-shield-check"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="bi bi-shield-check text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-2">No items pending authorization</h5>
                                <p class="text-muted">All verified items have been authorized!</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
let selectedAssets = [];
const CSRFTokenValue = '<?= CSRFProtection::generateToken() ?>';

// Initialize dashboard on load
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
});

// Initialize event listeners
function initializeEventListeners() {
    // Select all in quick actions panel
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.asset-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedAssets();

            // Sync header checkbox
            const headerCheckbox = document.getElementById('headerSelectAll');
            if (headerCheckbox) {
                headerCheckbox.checked = this.checked;
            }
        });
    }

    // Select all in table header
    const headerSelectAll = document.getElementById('headerSelectAll');
    if (headerSelectAll) {
        headerSelectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.asset-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedAssets();

            // Sync quick actions checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = this.checked;
            }
        });
    }

    // Bulk authorize button
    const bulkAuthorizeBtn = document.getElementById('bulkAuthorizeBtn');
    if (bulkAuthorizeBtn) {
        bulkAuthorizeBtn.addEventListener('click', function() {
            if (selectedAssets.length > 0) {
                bulkAuthorizeAssets();
            }
        });
    }
}

// Update selected assets array
function updateSelectedAssets() {
    selectedAssets = [];
    const checkboxes = document.querySelectorAll('.asset-checkbox:checked');
    checkboxes.forEach(cb => selectedAssets.push(cb.value));

    const bulkAuthorizeBtn = document.getElementById('bulkAuthorizeBtn');
    if (bulkAuthorizeBtn) {
        bulkAuthorizeBtn.disabled = selectedAssets.length === 0;
        if (selectedAssets.length > 0) {
            bulkAuthorizeBtn.innerHTML = `<i class="bi bi-shield-check me-1"></i>Authorize Selected (${selectedAssets.length})`;
        } else {
            bulkAuthorizeBtn.innerHTML = `<i class="bi bi-shield-check me-1"></i>Authorize Selected`;
        }
    }
}

// Authorize single asset with modal
function authorizeAsset(assetId) {
    if (!assetId) {
        showAlert('danger', 'Invalid item ID');
        return;
    }

    // Build review modal from the row data
    const checkbox = document.querySelector(`.asset-checkbox[value="${assetId}"]`);
    if (!checkbox) {
        showAlert('danger', 'Item not found');
        return;
    }

    const row = checkbox.closest('tr');
    const cells = row.querySelectorAll('td');

    const itemData = {
        id: assetId,
        name: cells[1]?.querySelector('strong')?.textContent.trim() || '',
        ref: cells[1]?.querySelector('small')?.textContent.replace('REF:', '').trim() || '',
        brand: cells[2]?.textContent.trim() || '',
        manufacturer: cells[3]?.textContent.trim() || '',
        category: cells[4]?.textContent.trim() || '',
        location: cells[5]?.textContent.trim() || '',
        quantity: cells[6]?.textContent.trim() || '',
        createdBy: cells[7]?.textContent.trim() || '',
        verifiedBy: cells[8]?.textContent.trim() || '',
        verificationDate: cells[9]?.textContent.trim() || ''
    };

    // Create and show review modal
    const modalHTML = `
        <div class="modal fade" id="authorizationReviewModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>Review Item for Authorization
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Please review the item details below before authorizing it as project property.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="30%">Item Name</th>
                                        <td><strong>${itemData.name}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Reference</th>
                                        <td>${itemData.ref}</td>
                                    </tr>
                                    <tr>
                                        <th>Brand</th>
                                        <td>${itemData.brand}</td>
                                    </tr>
                                    <tr>
                                        <th>Manufacturer</th>
                                        <td>${itemData.manufacturer}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>${itemData.category}</td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td>${itemData.location}</td>
                                    </tr>
                                    <tr>
                                        <th>Quantity</th>
                                        <td>${itemData.quantity}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>${itemData.createdBy}</td>
                                    </tr>
                                    <tr>
                                        <th>Verified By</th>
                                        <td>${itemData.verifiedBy}</td>
                                    </tr>
                                    <tr>
                                        <th>Verification Date</th>
                                        <td>${itemData.verificationDate}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <label for="authorizationNotes" class="form-label">Authorization Comments (Optional)</label>
                            <textarea class="form-control" id="authorizationNotes" rows="3"
                                      placeholder="Add any comments about this authorization..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" onclick="confirmAuthorization(${assetId})">
                            <i class="bi bi-shield-check me-1"></i>Authorize Item
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('authorizationReviewModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('authorizationReviewModal'));
    modal.show();

    // Clean up on close
    document.getElementById('authorizationReviewModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Confirm single authorization
function confirmAuthorization(assetId) {
    const notes = document.getElementById('authorizationNotes')?.value || '';

    const formData = new URLSearchParams();
    formData.append('asset_id', assetId);
    if (notes) {
        formData.append('authorization_notes', notes);
    }

    fetch('?route=assets/authorize-asset', {
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
        const modal = bootstrap.Modal.getInstance(document.getElementById('authorizationReviewModal'));
        if (modal) modal.hide();

        if (data.success) {
            showAlert('success', data.message || 'Item authorized successfully!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Failed to authorize item');
        }
    })
    .catch(error => {
        console.error('Authorization error:', error);
        showAlert('danger', 'An error occurred while authorizing the item');
    });
}

// Bulk authorize assets with review modal
function bulkAuthorizeAssets() {
    if (selectedAssets.length === 0) {
        showAlert('warning', 'Please select items to authorize');
        return;
    }

    // Build review table from selected rows
    const selectedRows = Array.from(document.querySelectorAll('.asset-checkbox:checked')).map(checkbox => {
        const row = checkbox.closest('tr');
        const cells = row.querySelectorAll('td');
        return {
            id: checkbox.value,
            name: cells[1]?.querySelector('strong')?.textContent.trim() || '',
            brand: cells[2]?.textContent.trim() || '',
            category: cells[4]?.textContent.trim() || '',
            location: cells[5]?.textContent.trim() || '',
            quantity: cells[6]?.textContent.trim() || '',
            createdBy: cells[7]?.textContent.trim() || '',
            verifiedBy: cells[8]?.textContent.trim() || ''
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
                        <th>Verified By</th>
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
                            <td>${item.verifiedBy}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    // Create review modal
    const modalHTML = `
        <div class="modal fade" id="batchAuthorizationModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>Review Items for Batch Authorization
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            You are about to authorize <strong>${selectedAssets.length} item${selectedAssets.length !== 1 ? 's' : ''}</strong> as project property.
                            Please review the items below before proceeding.
                        </div>

                        ${tableHTML}

                        <div class="mt-3">
                            <label for="batchAuthorizationNotes" class="form-label">Authorization Comments (Optional)</label>
                            <textarea class="form-control" id="batchAuthorizationNotes" rows="3"
                                      placeholder="Add comments for this batch authorization..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i>Cancel Review
                        </button>
                        <button type="button" class="btn btn-success" onclick="confirmBatchAuthorization()">
                            <i class="bi bi-shield-check me-1"></i>Authorize All ${selectedAssets.length} Items
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('batchAuthorizationModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('batchAuthorizationModal'));
    modal.show();

    // Clean up on close
    document.getElementById('batchAuthorizationModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Confirm batch authorization
function confirmBatchAuthorization() {
    const notes = document.getElementById('batchAuthorizationNotes')?.value || '';

    const formData = new URLSearchParams();

    // Add each asset ID as an array element
    selectedAssets.forEach(id => {
        formData.append('asset_ids[]', id);
    });

    if (notes) {
        formData.append('notes', notes);
    }

    fetch('?route=assets/batch-authorize', {
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
        const modal = bootstrap.Modal.getInstance(document.getElementById('batchAuthorizationModal'));
        if (modal) modal.hide();

        if (data.success) {
            showAlert('success', data.message || `Successfully authorized ${selectedAssets.length} items!`);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('danger', data.message || 'Failed to authorize items');
        }
    })
    .catch(error => {
        console.error('Batch authorization error:', error);
        showAlert('danger', 'An error occurred during batch authorization');
    });
}

// Utility function to show alerts
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert at top of main content
    const mainContent = document.querySelector('.card');
    if (mainContent && mainContent.parentNode) {
        mainContent.parentNode.insertBefore(alertDiv, mainContent);
    }

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
$pageTitle = 'Item Authorization Dashboard - ConstructLink™';
$pageHeader = 'Item Authorization Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'Authorization Dashboard', 'url' => '?route=assets/authorization-dashboard']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>