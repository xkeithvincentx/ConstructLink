<?php
/**
 * ConstructLink™ Approved Requests for Procurement
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="refreshData()">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-clipboard-check display-4 text-success mb-2"></i>
                <h3><?= count($approvedRequests) ?></h3>
                <p class="mb-0 small">Available Requests</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-currency-dollar display-4 text-info mb-2"></i>
                <h3>₱<?= number_format(array_sum(array_column($approvedRequests, 'estimated_cost')), 0) ?></h3>
                <p class="mb-0 small">Total Value</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-clock display-4 text-warning mb-2"></i>
                <h3><?= count(array_filter($approvedRequests, function($r) { return $r['date_needed'] && strtotime($r['date_needed']) < strtotime('+7 days'); })) ?></h3>
                <p class="mb-0 small">Urgent (7 days)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="bi bi-building display-4 text-primary mb-2"></i>
                <h3><?= count(array_unique(array_column($approvedRequests, 'project_id'))) ?></h3>
                <p class="mb-0 small">Projects</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="?route=procurement-orders/approved-requests" class="row g-3">
            <div class="col-md-3">
                <label for="project_filter" class="form-label">Project</label>
                <select class="form-select" id="project_filter" name="project_id">
                    <option value="">All Projects</option>
                    <?php 
                    $projects = array_unique(array_map(function($r) {
                        return ['id' => $r['project_id'], 'name' => $r['project_name']];
                    }, $approvedRequests), SORT_REGULAR);
                    foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>" <?= ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="urgency_filter" class="form-label">Urgency</label>
                <select class="form-select" id="urgency_filter" name="urgency">
                    <option value="">All Urgency Levels</option>
                    <option value="Critical" <?= ($_GET['urgency'] ?? '') == 'Critical' ? 'selected' : '' ?>>Critical</option>
                    <option value="Urgent" <?= ($_GET['urgency'] ?? '') == 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="Normal" <?= ($_GET['urgency'] ?? '') == 'Normal' ? 'selected' : '' ?>>Normal</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date Needed From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date Needed To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Apply Filters
                </button>
                <a href="?route=procurement-orders/approved-requests" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Approved Requests Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Approved Requests Available for Procurement</h6>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                <i class="bi bi-check-all me-1"></i>Select All
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                <i class="bi bi-x-square me-1"></i>Clear Selection
            </button>
            <button type="button" class="btn btn-primary" onclick="createBulkProcurement()" disabled id="bulkProcurementBtn">
                <i class="bi bi-cart-plus me-1"></i>Create Bulk PO
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($approvedRequests)): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="requestsTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll()">
                            </th>
                            <th>Request Details</th>
                            <th>Project</th>
                            <th>Requested By</th>
                            <th>Date Needed</th>
                            <th>Estimated Cost</th>
                            <th>Urgency</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedRequests as $request): ?>
                            <tr class="<?= $request['date_needed'] && strtotime($request['date_needed']) < time() ? 'table-warning' : '' ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input request-checkbox" 
                                           value="<?= $request['id'] ?>" onchange="updateBulkButton()">
                                </td>
                                <td>
                                    <div>
                                        <a href="?route=requests/view&id=<?= $request['id'] ?>" class="text-decoration-none fw-medium">
                                            Request #<?= $request['id'] ?>
                                        </a>
                                    </div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars(substr($request['description'], 0, 80)) ?>
                                        <?= strlen($request['description']) > 80 ? '...' : '' ?>
                                    </div>
                                    <?php if ($request['quantity']): ?>
                                        <small class="badge bg-light text-dark">
                                            <?= number_format($request['quantity']) ?> <?= htmlspecialchars($request['unit'] ?? 'units') ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($request['project_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($request['project_code']) ?></small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($request['requested_by_name']) ?></div>
                                    <small class="text-muted"><?= date('M j, Y', strtotime($request['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($request['date_needed']): ?>
                                        <div class="<?= strtotime($request['date_needed']) < time() ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($request['date_needed'])) ?>
                                        </div>
                                        <?php if (strtotime($request['date_needed']) < time()): ?>
                                            <small class="text-danger">Overdue</small>
                                        <?php elseif (strtotime($request['date_needed']) < strtotime('+7 days')): ?>
                                            <small class="text-warning">Urgent</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['estimated_cost']): ?>
                                        <div class="fw-medium">₱<?= number_format($request['estimated_cost'], 2) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=requests/view&id=<?= $request['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Request">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] === 'Approved' && in_array($userRole, $roleConfig['procurement-orders/createFromRequest'] ?? []) && empty($request['procurement_id'])): ?>
                                            <a href="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" 
                                               class="btn btn-primary" title="Create Procurement Order">
                                                <i class="bi bi-cart-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No approved requests found</h5>
                <p class="text-muted">All approved requests have already been processed or no requests match your filters.</p>
                <a href="?route=requests" class="btn btn-outline-primary">
                    <i class="bi bi-list me-1"></i>View All Requests
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Procurement Modal -->
<div class="modal fade" id="bulkProcurementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Bulk Procurement Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> This will create a single procurement order containing items from multiple requests.
                </div>
                
                <form id="bulkProcurementForm">
                    <div class="mb-3">
                        <label for="bulk_vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select class="form-select" id="bulk_vendor_id" name="vendor_id" required>
                            <option value="">Select Vendor</option>
                            <!-- Vendor options will be populated by JavaScript -->
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk_title" class="form-label">Procurement Order Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bulk_title" name="title" 
                               placeholder="e.g., Bulk Procurement for Multiple Site Requests" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="bulk_notes" name="notes" rows="3"
                                  placeholder="Additional notes for this bulk procurement order..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Selected Requests:</label>
                        <div id="selectedRequestsList" class="border rounded p-3 bg-light">
                            <!-- Selected requests will be populated by JavaScript -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBulkProcurement()">
                    <i class="bi bi-cart-plus me-1"></i>Create Bulk Procurement Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Selection management
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.request-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkButton();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
    updateBulkButton();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
    updateBulkButton();
}

function updateBulkButton() {
    const selectedCheckboxes = document.querySelectorAll('.request-checkbox:checked');
    const bulkBtn = document.getElementById('bulkProcurementBtn');
    
    if (selectedCheckboxes.length > 1) {
        bulkBtn.disabled = false;
        bulkBtn.innerHTML = `<i class="bi bi-cart-plus me-1"></i>Create Bulk PO (${selectedCheckboxes.length})`;
    } else {
        bulkBtn.disabled = true;
        bulkBtn.innerHTML = '<i class="bi bi-cart-plus me-1"></i>Create Bulk PO';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.request-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (selectedCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCheckboxes.length === allCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

function createBulkProcurement() {
    const selectedCheckboxes = document.querySelectorAll('.request-checkbox:checked');
    
    if (selectedCheckboxes.length < 2) {
        alert('Please select at least 2 requests for bulk procurement.');
        return;
    }
    
    // Populate selected requests in modal
    const selectedRequestsList = document.getElementById('selectedRequestsList');
    selectedRequestsList.innerHTML = '';
    
    selectedCheckboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const requestId = checkbox.value;
        const requestDetails = row.querySelector('td:nth-child(2)').textContent.trim();
        const project = row.querySelector('td:nth-child(3)').textContent.trim();
        const estimatedCost = row.querySelector('td:nth-child(6)').textContent.trim();
        
        const requestItem = document.createElement('div');
        requestItem.className = 'mb-2 p-2 border rounded bg-white';
        requestItem.innerHTML = `
            <div class="d-flex justify-content-between">
                <div>
                    <strong>Request #${requestId}</strong>
                    <div class="small text-muted">${requestDetails}</div>
                    <div class="small">Project: ${project}</div>
                </div>
                <div class="text-end">
                    <div class="fw-medium">${estimatedCost}</div>
                </div>
            </div>
        `;
        selectedRequestsList.appendChild(requestItem);
    });
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('bulkProcurementModal'));
    modal.show();
}

function submitBulkProcurement() {
    const form = document.getElementById('bulkProcurementForm');
    const vendorId = document.getElementById('bulk_vendor_id').value;
    const title = document.getElementById('bulk_title').value;
    
    if (!vendorId) {
        alert('Please select a vendor.');
        return;
    }
    
    if (!title.trim()) {
        alert('Please enter a title for the procurement order.');
        return;
    }
    
    const selectedCheckboxes = document.querySelectorAll('.request-checkbox:checked');
    const requestIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    // Create form data
    const formData = new FormData();
    formData.append('vendor_id', vendorId);
    formData.append('title', title);
    formData.append('notes', document.getElementById('bulk_notes').value);
    formData.append('request_ids', JSON.stringify(requestIds));
    
    // Submit via fetch (you would implement this endpoint)
    fetch('?route=procurement-orders/createBulk', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = `?route=procurement-orders/view&id=${data.procurement_order_id}&message=bulk_procurement_created`;
        } else {
            alert('Error creating bulk procurement order: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the bulk procurement order.');
    });
}

function refreshData() {
    window.location.reload();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBulkButton();
    
    // Load vendors for bulk procurement modal
    fetch('?route=api/vendors/search')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const vendorSelect = document.getElementById('bulk_vendor_id');
                data.vendors.forEach(vendor => {
                    const option = document.createElement('option');
                    option.value = vendor.id;
                    option.textContent = vendor.name;
                    vendorSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading vendors:', error));
});

// Table sorting and filtering
function sortTable(column) {
    // Implementation for table sorting
    console.log('Sorting by:', column);
}

function filterByUrgency(urgency) {
    const rows = document.querySelectorAll('#requestsTable tbody tr');
    
    rows.forEach(row => {
        if (urgency === 'all') {
            row.style.display = '';
        } else {
            const urgencyBadge = row.querySelector('.badge');
            const rowUrgency = urgencyBadge ? urgencyBadge.textContent.trim() : '';
            
            if (rowUrgency.toLowerCase() === urgency.toLowerCase()) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

function filterByProject(projectId) {
    const rows = document.querySelectorAll('#requestsTable tbody tr');
    
    rows.forEach(row => {
        if (projectId === 'all') {
            row.style.display = '';
        } else {
            // You would need to add data attributes to implement this
            row.style.display = '';
        }
    });
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Approved Requests - ConstructLink™';
$pageHeader = 'Approved Requests for Procurement';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'Approved Requests', 'url' => '?route=procurement-orders/approved-requests']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
