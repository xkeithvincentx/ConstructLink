<?php
/**
 * ConstructLink™ Create Procurement Order from Request
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-cart-plus me-2"></i>
        Create Procurement Order from Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Request
            </a>
        </div>
    </div>
</div>

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
        <!-- Procurement Order Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Procurement Order Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=procurement-orders/createFromRequest&request_id=<?= $request['id'] ?>" id="procurementOrderForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select class="form-select" id="vendor_id" name="vendor_id" required>
                                    <option value="">Select Vendor</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" <?= (isset($formData['vendor_id']) && $formData['vendor_id'] == $vendor['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= htmlspecialchars($formData['title'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_needed" class="form-label">Date Needed</label>
                                <input type="date" class="form-control" id="date_needed" name="date_needed" 
                                       value="<?= $formData['date_needed'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="budget_allocation" class="form-label">Budget Allocation</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="budget_allocation" name="budget_allocation" 
                                           step="0.01" value="<?= $formData['budget_allocation'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Scope and Work Breakdown -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="package_scope" class="form-label">Package Scope</label>
                                <textarea class="form-control" id="package_scope" name="package_scope" rows="3"><?= htmlspecialchars($formData['package_scope'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="work_breakdown" class="form-label">Work Breakdown</label>
                                <textarea class="form-control" id="work_breakdown" name="work_breakdown" rows="3"><?= htmlspecialchars($formData['work_breakdown'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Justification -->
                    <div class="mb-3">
                        <label for="justification" class="form-label">Justification</label>
                        <textarea class="form-control" id="justification" name="justification" rows="3"><?= htmlspecialchars($formData['justification'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Tax and Fee Information -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="vat_rate" class="form-label">VAT Rate (%)</label>
                                <input type="number" class="form-control" id="vat_rate" name="vat_rate" 
                                       step="0.01" value="<?= $formData['vat_rate'] ?? '12.00' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="ewt_rate" class="form-label">EWT Rate (%)</label>
                                <input type="number" class="form-control" id="ewt_rate" name="ewt_rate" 
                                       step="0.01" value="<?= $formData['ewt_rate'] ?? '2.00' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="handling_fee" class="form-label">Handling Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="handling_fee" name="handling_fee" 
                                           step="0.01" value="<?= $formData['handling_fee'] ?? '0' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="discount_amount" class="form-label">Discount Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                           step="0.01" value="<?= $formData['discount_amount'] ?? '0' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Items <span class="text-danger">*</span></h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItem()">
                                <i class="bi bi-plus-circle me-1"></i>Add Item
                            </button>
                        </div>
                        
                        <div id="items-container">
                            <?php foreach ($items as $index => $item): ?>
                                <div class="item-row border rounded p-3 mb-3" data-index="<?= $index ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0">Item #<?= $index + 1 ?></h6>
                                        <?php if ($index > 0): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="items[<?= $index ?>][item_name]" 
                                                       value="<?= htmlspecialchars($item['item_name'] ?? '') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="items[<?= $index ?>][category_id]">
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= $category['id'] ?>" <?= (isset($item['category_id']) && $item['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="items[<?= $index ?>][description]" rows="2"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="items[<?= $index ?>][quantity]" 
                                                       value="<?= $item['quantity'] ?? '' ?>" required min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Unit</label>
                                                <input type="text" class="form-control" name="items[<?= $index ?>][unit]" 
                                                       value="<?= htmlspecialchars($item['unit'] ?? 'pcs') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control" name="items[<?= $index ?>][unit_price]" 
                                                           step="0.01" value="<?= $item['unit_price'] ?? '' ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Total</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="text" class="form-control item-total" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Brand</label>
                                                <input type="text" class="form-control" name="items[<?= $index ?>][brand]" 
                                                       value="<?= htmlspecialchars($item['brand'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Model</label>
                                                <input type="text" class="form-control" name="items[<?= $index ?>][model]" 
                                                       value="<?= htmlspecialchars($item['model'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Specifications</label>
                                                <input type="text" class="form-control" name="items[<?= $index ?>][specifications]" 
                                                       value="<?= htmlspecialchars($item['specifications'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Item Notes</label>
                                        <textarea class="form-control" name="items[<?= $index ?>][item_notes]" rows="2"><?= htmlspecialchars($item['item_notes'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <div>
                            <button type="submit" name="action" value="save_draft" class="btn btn-outline-primary me-2">
                                <i class="bi bi-save me-1"></i>Save as Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Create Procurement Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Request Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>Source Request Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Request ID:</dt>
                    <dd class="col-sm-7">#<?= $request['id'] ?></dd>
                    
                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success">
                            <?= htmlspecialchars($request['status']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($request['project_name']) ?></dd>
                    
                    <dt class="col-sm-5">Requested By:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($request['requested_by_name']) ?></dd>
                    
                    <dt class="col-sm-5">Date Needed:</dt>
                    <dd class="col-sm-7"><?= $request['date_needed'] ? date('M j, Y', strtotime($request['date_needed'])) : 'Not specified' ?></dd>
                </dl>
                
                <div class="mt-3">
                    <h6>Description:</h6>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($request['description'])) ?></p>
                </div>
                
                <?php if ($request['estimated_cost']): ?>
                <div class="mt-3">
                    <h6>Estimated Cost:</h6>
                    <p class="text-muted">₱<?= number_format($request['estimated_cost'], 2) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="?route=requests/view&id=<?= $request['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Full Request
                    </a>
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
                <ul class="list-unstyled small">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Verify vendor information and capabilities
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Ensure all item specifications are accurate
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Review pricing and budget allocation
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Include delivery timeline and terms
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Double-check tax rates and fees
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let itemIndex = <?= count($items) ?>;

function addItem() {
    const container = document.getElementById('items-container');
    const itemHtml = `
        <div class="item-row border rounded p-3 mb-3" data-index="${itemIndex}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Item #${itemIndex + 1}</h6>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="items[${itemIndex}][item_name]" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="items[${itemIndex}][category_id]">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="items[${itemIndex}][description]" rows="2"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="items[${itemIndex}][quantity]" required min="1">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][unit]" value="pcs">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="items[${itemIndex}][unit_price]" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Total</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control item-total" readonly>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][brand]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][model]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Specifications</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][specifications]">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Item Notes</label>
                <textarea class="form-control" name="items[${itemIndex}][item_notes]" rows="2"></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
    itemIndex++;
    updateItemNumbers();
    attachCalculationEvents();
}

function removeItem(button) {
    button.closest('.item-row').remove();
    updateItemNumbers();
}

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((item, index) => {
        const title = item.querySelector('h6');
        title.textContent = `Item #${index + 1}`;
    });
}

function attachCalculationEvents() {
    document.querySelectorAll('.item-row').forEach(row => {
        const quantityInput = row.querySelector('input[name*="[quantity]"]');
        const priceInput = row.querySelector('input[name*="[unit_price]"]');
        const totalInput = row.querySelector('.item-total');
        
        function calculateTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = quantity * price;
            totalInput.value = total.toFixed(2);
        }
        
        quantityInput.addEventListener('input', calculateTotal);
        priceInput.addEventListener('input', calculateTotal);
        
        // Calculate initial total
        calculateTotal();
    });
}

// Initialize calculation events on page load
document.addEventListener('DOMContentLoaded', function() {
    attachCalculationEvents();
});

// Form validation
document.getElementById('procurementOrderForm').addEventListener('submit', function(e) {
    const vendorId = document.getElementById('vendor_id').value;
    const title = document.getElementById('title').value;
    const items = document.querySelectorAll('.item-row');
    
    if (!vendorId) {
        alert('Please select a vendor.');
        e.preventDefault();
        return false;
    }
    
    if (!title.trim()) {
        alert('Please enter a title for the procurement order.');
        e.preventDefault();
        return false;
    }
    
    if (items.length === 0) {
        alert('Please add at least one item.');
        e.preventDefault();
        return false;
    }
    
    // Validate each item
    let hasValidItem = false;
    items.forEach(item => {
        const itemName = item.querySelector('input[name*="[item_name]"]').value;
        const quantity = item.querySelector('input[name*="[quantity]"]').value;
        const unitPrice = item.querySelector('input[name*="[unit_price]"]').value;
        
        if (itemName.trim() && quantity && unitPrice) {
            hasValidItem = true;
        }
    });
    
    if (!hasValidItem) {
        alert('Please ensure at least one item has complete information (name, quantity, and unit price).');
        e.preventDefault();
        return false;
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create PO from Request - ConstructLink™';
$pageHeader = 'Create Procurement Order from Request #' . $request['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests'],
    ['title' => 'Request #' . $request['id'], 'url' => '?route=requests/view&id=' . $request['id']],
    ['title' => 'Create PO', 'url' => '?route=procurement-orders/createFromRequest&request_id=' . $request['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
