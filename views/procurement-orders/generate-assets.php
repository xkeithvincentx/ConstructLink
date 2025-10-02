<?php
// Prevent direct access
if (!defined('APP_ROOT')) {
    http_response_code(403);
    die('Direct access not permitted');
}

// Start output buffering
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? null;
$roleConfig = require APP_ROOT . '/config/roles.php';
?>
<?php if (in_array($user['role_name'], $roleConfig['procurement-orders/generateAssets'] ?? [])): ?>
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-square me-2"></i>
        Generate Assets
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders/view&id=<?= htmlspecialchars($procurementOrder['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order
        </a>
    </div>
</div>

<!-- Messages -->
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

<!-- Success Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messageMap = [
        'assets_generated' => ['type' => 'success', 'text' => 'Assets have been generated successfully.'],
    ];
    $messageInfo = $messageMap[$_GET['message']] ?? null;
    ?>
    <?php if ($messageInfo): ?>
        <div class="alert alert-<?= $messageInfo['type'] ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($messageInfo['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Generate Assets Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-plus-square me-2"></i>Select Items to Generate Assets
                </h6>
            </div>
            <div class="card-body">
                <?php if ($procurementOrder['status'] !== 'Received' && $procurementOrder['delivery_status'] !== 'Delivered'): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Notice:</strong> Assets can only be generated from received or delivered procurement orders.
                    </div>
                <?php elseif (empty($availableItems)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>No Items Available:</strong> All items from this procurement order have already been converted to assets or are not eligible for asset generation.
                    </div>
                    
                    <?php if (!empty($allItems)): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Asset Generation Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-center">Total Qty</th>
                                                <th class="text-center">Assets Generated</th>
                                                <th class="text-center">Available</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allItems as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium"><?= htmlspecialchars($item['item_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($item['description'] ?? '') ?></small>
                                                    </td>
                                                    <td class="text-center"><?= number_format($item['total_quantity']) ?></td>
                                                    <td class="text-center"><?= number_format($item['assets_generated']) ?></td>
                                                    <td class="text-center"><?= number_format($item['available_for_generation']) ?></td>
                                                    <td class="text-center">
                                                        <?php if ($item['available_for_generation'] > 0): ?>
                                                            <span class="badge bg-warning">Partial</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">Complete</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Role-based guidance -->
                    <?php if (canGenerateAssets($procurementOrder, $userRole)): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Warehouseman Note:</strong> If you need to generate additional assets or if there was an error during receipt confirmation, please contact your Procurement Officer or System Administrator.
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Asset Generation:</strong> Select the items and quantities you want to convert into individual assets. Each asset will be created with a unique reference number and can be tracked separately.
                    </div>
                    
                    <!-- Role-based permissions note -->
                    <?php if (canGenerateAssets($procurementOrder, $userRole)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Permission Confirmed:</strong> As a Warehouseman, you have permission to generate assets from received procurement orders.
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="?route=procurement-orders/generateAssets&id=<?= htmlspecialchars($procurementOrder['id']) ?>" id="generateAssetsForm">
                        <?= CSRFProtection::getTokenField() ?>
                        
                        <!-- Items List -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="bi bi-list-ul me-2"></i>Available Items for Asset Generation
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                    <label class="form-check-label" for="selectAll">
                                                        Select All
                                                    </label>
                                                </div>
                                            </th>
                                            <th>Item</th>
                                            <th class="text-center">Total Qty</th>
                                            <th class="text-center">Already Generated</th>
                                            <th class="text-center">Available</th>
                                            <th class="text-center">Unit Price</th>
                                            <th class="text-center">Generate Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availableItems as $item): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input item-checkbox" 
                                                               type="checkbox" 
                                                               name="items[<?= $item['id'] ?>][selected]" 
                                                               value="1"
                                                               id="item_<?= $item['id'] ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-medium"><?= htmlspecialchars($item['item_name']) ?></div>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['specifications'])): ?>
                                                        <br><small class="text-info">Specs: <?= htmlspecialchars($item['specifications']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['model']) || !empty($item['brand'])): ?>
                                                        <br><small class="text-secondary">
                                                            <?= htmlspecialchars($item['brand'] ?? '') ?>
                                                            <?= !empty($item['brand']) && !empty($item['model']) ? ' - ' : '' ?>
                                                            <?= htmlspecialchars($item['model'] ?? '') ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= number_format($item['quantity_received'] ?? $item['quantity']) ?></span>
                                                    <br><small class="text-muted"><?= htmlspecialchars($item['unit'] ?? 'pcs') ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <?php 
                                                    $alreadyGenerated = ($item['quantity_received'] ?? $item['quantity']) - $item['available_for_generation'];
                                                    ?>
                                                    <span class="badge bg-secondary"><?= number_format($alreadyGenerated) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= number_format($item['available_for_generation']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    ₱<?= number_format($item['unit_price'], 2) ?>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number" 
                                                           name="items[<?= $item['id'] ?>][quantity]" 
                                                           class="form-control form-control-sm text-center quantity-input" 
                                                           min="0" 
                                                           max="<?= $item['available_for_generation'] ?>" 
                                                           value="0"
                                                           style="width: 80px;"
                                                           data-item-id="<?= $item['id'] ?>">
                                                    <small class="text-muted">Max: <?= $item['available_for_generation'] ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Asset Generation Notes -->
                        <div class="mb-4">
                            <label for="generation_notes" class="form-label">
                                <i class="bi bi-clipboard-text me-1"></i>Asset Generation Notes
                            </label>
                            <textarea name="generation_notes" 
                                      id="generation_notes" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Enter any notes about the asset generation process..."></textarea>
                            <div class="form-text">
                                Optional notes that will be recorded with the generated assets.
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="?route=procurement-orders/view&id=<?= htmlspecialchars($procurementOrder['id']) ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="generateButton" disabled>
                                <i class="bi bi-plus-square me-1"></i>Generate Assets
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

                    <dt class="col-sm-5">Title:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['title']) ?></dd>

                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $statusClasses = [
                            'Draft' => 'bg-secondary',
                            'Pending' => 'bg-warning',
                            'Reviewed' => 'bg-info',
                            'Approved' => 'bg-success',
                            'Rejected' => 'bg-danger',
                            'Received' => 'bg-primary'
                        ];
                        $statusClass = $statusClasses[$procurementOrder['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= htmlspecialchars($procurementOrder['status']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Vendor:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($procurementOrder['vendor_name']) ?></dd>

                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-light text-dark">
                            <?= htmlspecialchars($procurementOrder['project_name']) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Received Date:</dt>
                    <dd class="col-sm-7"><?= isset($procurementOrder['received_at']) && $procurementOrder['received_at'] ? date('M j, Y', strtotime($procurementOrder['received_at'])) : 'Not received yet' ?></dd>
                </dl>
            </div>
        </div>

        <!-- Asset Generation Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Asset Generation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Each asset will get a unique reference number
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Assets will be assigned to the procurement project
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Unit cost will be copied from procurement item
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Assets will be marked as "Available" status
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Asset history will link back to this PO
                    </li>
                </ul>
            </div>
        </div>

        <!-- Generation Summary -->
        <div class="card mt-3" id="generationSummary" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-calculator me-2"></i>Generation Summary
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-7">Total Items Selected:</dt>
                    <dd class="col-sm-5 text-end" id="totalItems">0</dd>
                    
                    <dt class="col-sm-7">Total Assets to Generate:</dt>
                    <dd class="col-sm-5 text-end" id="totalAssets">0</dd>
                    
                    <dt class="col-sm-7">Estimated Total Value:</dt>
                    <dd class="col-sm-5 text-end" id="totalValue">₱0.00</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const generateButton = document.getElementById('generateButton');
    const generationSummary = document.getElementById('generationSummary');
    
    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            const itemId = checkbox.name.match(/\[(\d+)\]/)[1];
            const quantityInput = document.querySelector(`input[data-item-id="${itemId}"]`);
            if (this.checked) {
                quantityInput.value = quantityInput.getAttribute('max');
            } else {
                quantityInput.value = 0;
            }
        });
        updateSummary();
    });
    
    // Individual checkbox functionality
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemId = this.name.match(/\[(\d+)\]/)[1];
            const quantityInput = document.querySelector(`input[data-item-id="${itemId}"]`);
            
            if (this.checked) {
                quantityInput.value = quantityInput.getAttribute('max');
            } else {
                quantityInput.value = 0;
            }
            
            updateSelectAllState();
            updateSummary();
        });
    });
    
    // Quantity input functionality
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const itemId = this.getAttribute('data-item-id');
            const checkbox = document.querySelector(`input[name="items[${itemId}][selected]"]`);
            
            if (parseInt(this.value) > 0) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
            
            updateSelectAllState();
            updateSummary();
        });
    });
    
    // Update select all checkbox state
    function updateSelectAllState() {
        const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
        selectAllCheckbox.checked = checkedBoxes.length === itemCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < itemCheckboxes.length;
    }
    
    // Update generation summary
    function updateSummary() {
        let totalItems = 0;
        let totalAssets = 0;
        let totalValue = 0;
        
        quantityInputs.forEach(input => {
            const quantity = parseInt(input.value) || 0;
            if (quantity > 0) {
                totalItems++;
                totalAssets += quantity;
                
                // Get unit price from the row
                const row = input.closest('tr');
                const priceText = row.querySelector('td:nth-child(6)').textContent; // Changed to 6 for Unit Price
                const unitPrice = parseFloat(priceText.replace(/[₱,]/g, '')) || 0;
                totalValue += quantity * unitPrice;
            }
        });
        
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('totalAssets').textContent = totalAssets;
        document.getElementById('totalValue').textContent = '₱' + totalValue.toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Show/hide summary and enable/disable button
        if (totalAssets > 0) {
            generationSummary.style.display = 'block';
            generateButton.disabled = false;
        } else {
            generationSummary.style.display = 'none';
            generateButton.disabled = true;
        }
    }
    
    // Form validation
    const generateAssetsForm = document.getElementById('generateAssetsForm');
    if (generateAssetsForm) {
        generateAssetsForm.addEventListener('submit', function(e) {
            let hasSelectedItems = false;
            
            quantityInputs.forEach(input => {
                if (parseInt(input.value) > 0) {
                    hasSelectedItems = true;
                }
            });
            
            if (!hasSelectedItems) {
                e.preventDefault();
                alert('Please select at least one item and specify the quantity to generate assets.');
                return false;
            }
            
            // Confirm generation
            const totalAssets = document.getElementById('totalAssets').textContent;
            if (!confirm(`Are you sure you want to generate ${totalAssets} assets? This action cannot be undone.`)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Auto-fill all quantities button
    const autoFillBtn = document.createElement('button');
    autoFillBtn.type = 'button';
    autoFillBtn.className = 'btn btn-outline-primary btn-sm mb-3';
    autoFillBtn.innerHTML = '<i class="bi bi-magic me-1"></i>Auto-fill All Quantities';
    autoFillBtn.addEventListener('click', function() {
        quantityInputs.forEach(input => {
            const maxQty = parseInt(input.getAttribute('max'));
            input.value = maxQty;
            
            const itemId = input.getAttribute('data-item-id');
            const checkbox = document.querySelector(`input[name="items[${itemId}][selected]"]`);
            checkbox.checked = true;
        });
        
        updateSelectAllState();
        updateSummary();
    });
    
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.parentNode.insertBefore(autoFillBtn, tableContainer);
    }
    
    // Initialize
    updateSummary();
});
</script>
<?php else: ?>
<div class="alert alert-danger mt-4">You do not have permission to generate assets for this procurement order.</div>
<?php endif; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
