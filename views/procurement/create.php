<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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

<div class="row">
    <div class="col-lg-8">
        <!-- Procurement Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-plus me-2"></i>Procurement Order Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=procurement/create" id="procurementForm" enctype="multipart/form-data">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <!-- Request Selection (if applicable) -->
                    <?php if (!empty($approvedRequests)): ?>
                        <div class="mb-3">
                            <label for="request_id" class="form-label">Link to Request (Optional)</label>
                            <select class="form-select" id="request_id" name="request_id">
                                <option value="">Select a request to link...</option>
                                <?php foreach ($approvedRequests as $request): ?>
                                    <option value="<?= $request['id'] ?>" 
                                            <?= ($formData['request_id'] ?? '') == $request['id'] ? 'selected' : '' ?>
                                            data-project="<?= $request['project_id'] ?>"
                                            data-description="<?= htmlspecialchars($request['description']) ?>"
                                            data-quantity="<?= $request['quantity'] ?>"
                                            data-unit="<?= htmlspecialchars($request['unit'] ?? 'pcs') ?>"
                                            data-date-needed="<?= $request['date_needed'] ?>">
                                        Request #<?= $request['id'] ?> - <?= htmlspecialchars(substr($request['description'], 0, 50)) ?>...
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Link this procurement to an approved request</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select class="form-select" id="vendor_id" name="vendor_id" required>
                                    <option value="">Select Vendor</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" <?= ($formData['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item_name" name="item_name" 
                               value="<?= htmlspecialchars($formData['item_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?= htmlspecialchars($formData['quantity'] ?? '') ?>" 
                                       min="1" step="1" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <select class="form-select" id="unit" name="unit">
                                    <option value="pcs" <?= ($formData['unit'] ?? 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces</option>
                                    <option value="set" <?= ($formData['unit'] ?? '') === 'set' ? 'selected' : '' ?>>Set</option>
                                    <option value="box" <?= ($formData['unit'] ?? '') === 'box' ? 'selected' : '' ?>>Box</option>
                                    <option value="kg" <?= ($formData['unit'] ?? '') === 'kg' ? 'selected' : '' ?>>Kilogram</option>
                                    <option value="m" <?= ($formData['unit'] ?? '') === 'm' ? 'selected' : '' ?>>Meter</option>
                                    <option value="sqm" <?= ($formData['unit'] ?? '') === 'sqm' ? 'selected' : '' ?>>Square Meter</option>
                                    <option value="lot" <?= ($formData['unit'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit_price" class="form-label">Unit Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                       value="<?= htmlspecialchars($formData['unit_price'] ?? '') ?>" 
                                       min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="handling_fee" class="form-label">Handling Fee (₱)</label>
                                <input type="number" class="form-control" id="handling_fee" name="handling_fee" 
                                       value="<?= htmlspecialchars($formData['handling_fee'] ?? '0') ?>" 
                                       min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_needed" class="form-label">Date Needed</label>
                                <input type="date" class="form-control" id="date_needed" name="date_needed" 
                                       value="<?= htmlspecialchars($formData['date_needed'] ?? '') ?>" 
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="package_scope" class="form-label">Package Scope</label>
                        <textarea class="form-control" id="package_scope" name="package_scope" rows="2" 
                                  placeholder="Describe what's included in this procurement package..."><?= htmlspecialchars($formData['package_scope'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quote_file" class="form-label">Vendor Quote (Optional)</label>
                        <input type="file" class="form-control" id="quote_file" name="quote_file" 
                               accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">Upload vendor quote (PDF or image files, max 5MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Additional notes or special instructions..."><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=procurement" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Create Procurement Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Cost Calculation -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-calculator me-2"></i>Cost Calculation
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">Subtotal:</div>
                    <div class="col-6 text-end">₱<span id="calc-subtotal">0.00</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">VAT (12%):</div>
                    <div class="col-6 text-end">₱<span id="calc-vat">0.00</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">Handling Fee:</div>
                    <div class="col-6 text-end">₱<span id="calc-handling">0.00</span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6">EWT (2%):</div>
                    <div class="col-6 text-end">-₱<span id="calc-ewt">0.00</span></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6"><strong>Net Total:</strong></div>
                    <div class="col-6 text-end"><strong>₱<span id="calc-total">0.00</span></strong></div>
                </div>
            </div>
        </div>
        
        <!-- Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Procurement Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Ensure vendor quotation is attached
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Verify project budget availability
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Include detailed item specifications
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Set realistic delivery dates
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-info-circle text-info me-2"></i>
                        Orders require Finance Director approval
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-fill from request selection
    const requestSelect = document.getElementById('request_id');
    if (requestSelect) {
        requestSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('project_id').value = selectedOption.dataset.project || '';
                document.getElementById('item_name').value = selectedOption.dataset.description || '';
                document.getElementById('description').value = selectedOption.dataset.description || '';
                document.getElementById('quantity').value = selectedOption.dataset.quantity || '';
                document.getElementById('unit').value = selectedOption.dataset.unit || 'pcs';
                document.getElementById('date_needed').value = selectedOption.dataset.dateNeeded || '';
                
                // Trigger calculation update
                updateCalculation();
            }
        });
    }
    
    // Real-time cost calculation
    function updateCalculation() {
        const quantity = parseFloat(document.getElementById('quantity').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        const handlingFee = parseFloat(document.getElementById('handling_fee').value) || 0;
        
        const subtotal = quantity * unitPrice;
        const vat = subtotal * 0.12; // 12% VAT
        const ewt = subtotal * 0.02; // 2% EWT
        const netTotal = subtotal + vat + handlingFee - ewt;
        
        document.getElementById('calc-subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('calc-vat').textContent = vat.toFixed(2);
        document.getElementById('calc-handling').textContent = handlingFee.toFixed(2);
        document.getElementById('calc-ewt').textContent = ewt.toFixed(2);
        document.getElementById('calc-total').textContent = netTotal.toFixed(2);
    }
    
    // Attach calculation update to relevant fields
    ['quantity', 'unit_price', 'handling_fee'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateCalculation);
        }
    });
    
    // Initial calculation
    updateCalculation();
    
    // Form validation
    document.getElementById('procurementForm').addEventListener('submit', function(e) {
        const quantity = parseFloat(document.getElementById('quantity').value) || 0;
        const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
        
        if (quantity <= 0) {
            e.preventDefault();
            alert('Please enter a valid quantity greater than 0.');
            document.getElementById('quantity').focus();
            return false;
        }
        
        if (unitPrice <= 0) {
            e.preventDefault();
            alert('Please enter a valid unit price greater than 0.');
            document.getElementById('unit_price').focus();
            return false;
        }
        
        // Confirm submission
        const netTotal = parseFloat(document.getElementById('calc-total').textContent);
        if (netTotal > 100000) { // High value confirmation
            if (!confirm(`This is a high-value procurement order (₱${netTotal.toLocaleString()}). Are you sure you want to proceed?`)) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Procurement Order - ConstructLink™';
$pageHeader = 'Create Procurement Order';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement', 'url' => '?route=procurement'],
    ['title' => 'Create Order', 'url' => '?route=procurement/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
