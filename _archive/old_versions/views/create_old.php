<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle me-2"></i>
        <?= $pageHeader ?? 'Create New Asset' ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=assets" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Assets
        </a>
    </div>
</div>

<!-- Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Asset Creation Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=assets/create" id="assetForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="ref" class="form-label">Asset Reference</label>
                            <input type="text" class="form-control" id="ref" name="ref" 
                                   value="<?= htmlspecialchars($formData['ref'] ?? '') ?>"
                                   placeholder="Leave blank to auto-generate">
                            <div class="form-text">Leave blank to auto-generate with system prefix</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="Detailed description of the asset..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Classification -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-tags me-1"></i>Classification
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($formData['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select Project</option>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" 
                                                <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                            <?php if (!empty($project['location'])): ?>
                                                - <?= htmlspecialchars($project['location']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Procurement & Vendor Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-building me-1"></i>Procurement & Vendor Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="procurement_order_id" class="form-label">Procurement Order</label>
                            <select class="form-select" id="procurement_order_id" name="procurement_order_id">
                                <option value="">Select Procurement Order (Optional)</option>
                                <?php if (!empty($procurementOrders)): ?>
                                    <?php foreach ($procurementOrders as $order): ?>
                                        <option value="<?= $order['id'] ?>" 
                                                data-vendor="<?= htmlspecialchars($order['vendor_name'] ?? '') ?>"
                                                data-vendor-id="<?= $order['vendor_id'] ?? '' ?>"
                                                <?= ($formData['procurement_order_id'] ?? '') == $order['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($order['po_number'] ?: '#' . $order['id']) ?> - 
                                            <?= htmlspecialchars($order['title']) ?>
                                            (<?= $order['item_count'] ?? 0 ?> items)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Link to procurement order if asset was purchased</div>
                        </div>
                        
                        <!-- Procurement item selection -->
                        <div class="col-md-6" id="procurement_item_container" style="display: none;">
                            <label for="procurement_item_id" class="form-label">Procurement Item <span class="text-danger">*</span></label>
                            <select class="form-select" id="procurement_item_id" name="procurement_item_id">
                                <option value="">Select Item</option>
                            </select>
                            <div class="form-text">Select specific item from procurement order</div>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select class="form-select" id="vendor_id" name="vendor_id">
                                <option value="">Select Vendor</option>
                                <?php if (!empty($vendors)): ?>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" 
                                                <?= ($formData['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <label for="maker_id" class="form-label">Manufacturer</label>
                            <select class="form-select" id="maker_id" name="maker_id">
                                <option value="">Select Manufacturer</option>
                                <?php if (!empty($makers)): ?>
                                    <?php foreach ($makers as $maker): ?>
                                        <option value="<?= $maker['id'] ?>" 
                                                <?= ($formData['maker_id'] ?? '') == $maker['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($maker['name']) ?>
                                            <?php if (!empty($maker['country'])): ?>
                                                (<?= htmlspecialchars($maker['country']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <label for="client_id" class="form-label">Client (if client-supplied)</label>
                            <select class="form-select" id="client_id" name="client_id">
                                <option value="">Select Client</option>
                                <?php if (!empty($clients)): ?>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" 
                                                <?= ($formData['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mt-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_client_supplied" name="is_client_supplied" 
                                       <?= !empty($formData['is_client_supplied']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_client_supplied">
                                    Client Supplied Asset
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Technical Specifications -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-gear me-1"></i>Technical Specifications
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?= htmlspecialchars($formData['model'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                   value="<?= htmlspecialchars($formData['serial_number'] ?? '') ?>">
                        </div>
                        
                        <div class="col-12 mt-3">
                            <label for="specifications" class="form-label">Detailed Specifications</label>
                            <textarea class="form-control" id="specifications" name="specifications" rows="3"
                                      placeholder="Technical specifications, dimensions, capacity, etc..."><?= htmlspecialchars($formData['specifications'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Financial Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-currency-dollar me-1"></i>Financial Information
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="acquired_date" class="form-label">Acquired Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="acquired_date" name="acquired_date" 
                                   value="<?= htmlspecialchars($formData['acquired_date'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="acquisition_cost" class="form-label">Acquisition Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="acquisition_cost" name="acquisition_cost" 
                                       step="0.01" min="0" value="<?= htmlspecialchars($formData['acquisition_cost'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="unit_cost" class="form-label">Unit Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                       step="0.01" min="0" value="<?= htmlspecialchars($formData['unit_cost'] ?? '') ?>">
                            </div>
                            <div class="form-text">Individual unit cost if different from total</div>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                            <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                   value="<?= htmlspecialchars($formData['warranty_expiry'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Location & Condition -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-geo-alt me-1"></i>Location & Condition
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="location" class="form-label">Current Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?= htmlspecialchars($formData['location'] ?? '') ?>"
                                   placeholder="Warehouse, Site office, etc.">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="condition_notes" class="form-label">Condition Notes</label>
                            <textarea class="form-control" id="condition_notes" name="condition_notes" rows="2"
                                      placeholder="Current condition, any defects, etc..."><?= htmlspecialchars($formData['condition_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=assets" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Create Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Creation Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Asset Creation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="bi bi-info-circle me-1"></i>Important Notes
                    </h6>
                    <ul class="mb-0 small">
                        <li>Asset reference will be auto-generated if left blank</li>
                        <li>Link to procurement order for purchased items</li>
                        <li>Mark as client-supplied if provided by client</li>
                        <li>Include detailed specifications for technical assets</li>
                        <li>QR code will be automatically generated</li>
                    </ul>
                </div>
                
                <h6 class="mt-3">Required Fields</h6>
                <ul class="small text-muted">
                    <li>Asset Name</li>
                    <li>Category</li>
                    <li>Project Assignment</li>
                    <li>Acquired Date</li>
                </ul>
                
                <h6 class="mt-3">Asset Status</h6>
                <p class="small text-muted">
                    New assets are automatically set to "Available" status and can be immediately used for withdrawals and transfers.
                </p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=procurement-orders" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-cart me-1"></i>View Procurement Orders
                    </a>
                    <a href="?route=categories" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-tags me-1"></i>Manage Categories
                    </a>
                    <a href="?route=vendors" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-building me-1"></i>Manage Vendors
                    </a>
                    <a href="?route=makers" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-gear me-1"></i>Manage Manufacturers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('assetForm');
    const procurementOrderSelect = document.getElementById('procurement_order_id');
    const procurementItemContainer = document.getElementById('procurement_item_container');
    const procurementItemSelect = document.getElementById('procurement_item_id');
    const vendorSelect = document.getElementById('vendor_id');
    const acquisitionCostInput = document.getElementById('acquisition_cost');
    const unitCostInput = document.getElementById('unit_cost');
    
    // Handle procurement order selection
    procurementOrderSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const vendorName = selectedOption.getAttribute('data-vendor');
        const vendorId = selectedOption.getAttribute('data-vendor-id');
        
        if (this.value) {
            // Auto-populate vendor if available
            if (vendorId) {
                vendorSelect.value = vendorId;
            }
            
            // Show procurement item selection
            procurementItemContainer.style.display = 'block';
            procurementItemSelect.required = true;
            loadProcurementItems(this.value);
        } else {
            procurementItemContainer.style.display = 'none';
            procurementItemSelect.required = false;
            procurementItemSelect.innerHTML = '<option value="">Select Item</option>';
        }
    });
    
    // Load procurement items for selected order
    function loadProcurementItems(procurementOrderId) {
        // Clear existing options
        procurementItemSelect.innerHTML = '<option value="">Loading items...</option>';
        
        // Fetch items via AJAX
        fetch(`?route=api/procurement-orders/items&id=${procurementOrderId}`)
            .then(response => response.json())
            .then(data => {
                procurementItemSelect.innerHTML = '<option value="">Select Item</option>';
                
                if (data.success && data.items) {
                    data.items.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = `${item.item_name} - ₱${parseFloat(item.unit_price).toFixed(2)} (Qty: ${item.quantity})`;
                        option.setAttribute('data-cost', item.unit_price);
                        option.setAttribute('data-name', item.item_name);
                        option.setAttribute('data-brand', item.brand || '');
                        option.setAttribute('data-model', item.model || '');
                        option.setAttribute('data-specifications', item.specifications || '');
                        option.setAttribute('data-category-id', item.category_id || '');
                        procurementItemSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No items available';
                    procurementItemSelect.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Error loading procurement items:', error);
                procurementItemSelect.innerHTML = '<option value="">Error loading items</option>';
            });
    }
    
    // Handle procurement item selection
    procurementItemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const itemCost = selectedOption.getAttribute('data-cost');
            const itemName = selectedOption.getAttribute('data-name');
            const itemBrand = selectedOption.getAttribute('data-brand');
            const itemModel = selectedOption.getAttribute('data-model');
            const itemSpecs = selectedOption.getAttribute('data-specifications');
            const categoryId = selectedOption.getAttribute('data-category-id');
            
            // Auto-populate asset fields from procurement item
            if (itemName && !document.getElementById('name').value) {
                let assetName = itemName;
                if (itemBrand) {
                    assetName = `${itemBrand} ${itemName}`;
                }
                document.getElementById('name').value = assetName;
            }
            
            if (itemModel && !document.getElementById('model').value) {
                document.getElementById('model').value = itemModel;
            }
            
            if (itemSpecs && !document.getElementById('specifications').value) {
                document.getElementById('specifications').value = itemSpecs;
            }
            
            if (categoryId && !document.getElementById('category_id').value) {
                document.getElementById('category_id').value = categoryId;
            }
            
            if (itemCost) {
                if (!acquisitionCostInput.value) {
                    acquisitionCostInput.value = itemCost;
                }
                if (!unitCostInput.value) {
                    unitCostInput.value = itemCost;
                }
            }
        }
    });
    
    // Auto-populate unit cost from acquisition cost
    acquisitionCostInput.addEventListener('input', function() {
        if (this.value && !unitCostInput.value) {
            unitCostInput.value = this.value;
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        const requiredFields = ['name', 'category_id', 'project_id', 'acquired_date'];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Check if procurement item is required
        if (procurementOrderSelect.value && !procurementItemSelect.value) {
            procurementItemSelect.classList.add('is-invalid');
            isValid = false;
        } else {
            procurementItemSelect.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Real-time validation feedback
    const inputs = form.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });
    
    // Initialize procurement item container visibility
    if (procurementOrderSelect.value) {
        procurementItemContainer.style.display = 'block';
        procurementItemSelect.required = true;
        loadProcurementItems(procurementOrderSelect.value);
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
