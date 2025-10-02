<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clock-history me-2"></i>
        Create Retroactive Procurement Order
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Procurement Orders
        </a>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Retroactive Documentation:</strong> Use this form to document purchases that were made without a formal PO due to emergency situations, direct pickups, or other special circumstances.
</div>

<!-- Display Messages -->
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

<?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
    <form method="POST" action="?route=procurement-orders/create-retrospective" id="retroactivePOForm" enctype="multipart/form-data">
        <?= CSRFProtection::getTokenField() ?>
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Retroactive Information -->
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Retroactive Documentation Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_state" class="form-label">Current Status of Items <span class="text-danger">*</span></label>
                                <select name="current_state" id="current_state" class="form-select" required>
                                    <option value="not_delivered" <?= ($formData['current_state'] ?? '') == 'not_delivered' ? 'selected' : '' ?>>
                                        Purchased but not yet delivered
                                    </option>
                                    <option value="delivered" <?= ($formData['current_state'] ?? '') == 'delivered' ? 'selected' : '' ?>>
                                        Delivered but not yet received in warehouse
                                    </option>
                                    <option value="received" <?= ($formData['current_state'] ?? '') == 'received' ? 'selected' : '' ?>>
                                        Already received and in use
                                    </option>
                                </select>
                                <div class="form-text">Select the current state to determine appropriate workflow</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="reason" class="form-label">Reason for No PO <span class="text-danger">*</span></label>
                                <select name="reason" id="reason" class="form-select" required>
                                    <option value="">Select Reason</option>
                                    <?php foreach ($reasonOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($formData['reason'] ?? '') == $value ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the reason why PO was not issued initially</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Basic Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select" required>
                                    <option value="">Select Vendor</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <option value="<?= $vendor['id'] ?>" <?= ($formData['vendor_id'] ?? '') == $vendor['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vendor['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">Select Project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">PO Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   value="<?= htmlspecialchars($formData['title'] ?? '') ?>" 
                                   placeholder="Brief description of the procurement" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="delivery_method" class="form-label">
                                    <span id="delivery-method-label">Delivery Method</span>
                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                       title="Options will update based on selected item categories"></i>
                                </label>
                                <select name="delivery_method" id="delivery_method" class="form-select">
                                    <option value="">Select Method</option>
                                    <!-- Physical Delivery Options -->
                                    <optgroup id="physical-delivery-group" label="Physical Delivery">
                                        <option value="Pickup" <?= ($formData['delivery_method'] ?? '') == 'Pickup' ? 'selected' : '' ?>>Pickup</option>
                                        <option value="Direct Delivery" <?= ($formData['delivery_method'] ?? '') == 'Direct Delivery' ? 'selected' : '' ?>>Direct Delivery</option>
                                        <option value="Courier" <?= ($formData['delivery_method'] ?? '') == 'Courier' ? 'selected' : '' ?>>Courier</option>
                                        <option value="Other" <?= ($formData['delivery_method'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </optgroup>
                                    <!-- Service Delivery Options -->
                                    <optgroup id="service-delivery-group" label="Service Delivery" style="display: none;">
                                        <option value="On-site Service" <?= ($formData['delivery_method'] ?? '') == 'On-site Service' ? 'selected' : '' ?>>On-site Service</option>
                                        <option value="Remote Service" <?= ($formData['delivery_method'] ?? '') == 'Remote Service' ? 'selected' : '' ?>>Remote Service</option>
                                        <option value="Digital Delivery" <?= ($formData['delivery_method'] ?? '') == 'Digital Delivery' ? 'selected' : '' ?>>Digital Delivery</option>
                                        <option value="Email Delivery" <?= ($formData['delivery_method'] ?? '') == 'Email Delivery' ? 'selected' : '' ?>>Email Delivery</option>
                                        <option value="Service Completion" <?= ($formData['delivery_method'] ?? '') == 'Service Completion' ? 'selected' : '' ?>>Service Completion</option>
                                        <option value="N/A" <?= ($formData['delivery_method'] ?? '') == 'N/A' ? 'selected' : '' ?>>N/A</option>
                                    </optgroup>
                                </select>
                                <div class="form-text" id="delivery-method-help">
                                    Select how items were delivered or services were completed
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="delivery_location" class="form-label">
                                    <span id="delivery-location-label">Delivery Location</span>
                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                       title="Location will be context-aware based on item categories"></i>
                                </label>
                                <input type="text" name="delivery_location" id="delivery_location" class="form-control" 
                                       value="<?= htmlspecialchars($formData['delivery_location'] ?? '') ?>" 
                                       placeholder="Where items were delivered or services were performed">
                                <div class="form-text" id="delivery-location-help">
                                    Specify the location for physical delivery or service provision
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Items
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()">
                            <i class="bi bi-plus me-1"></i>Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="itemsList">
                            <!-- Items will be added here -->
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Add all items that were purchased. For retroactive documentation, you can specify quantities already received.
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-file-text me-2"></i>Additional Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="justification" class="form-label">Justification</label>
                            <textarea name="justification" id="justification" class="form-control" rows="3" 
                                      placeholder="Explain why this purchase was necessary and why no PO was issued initially"><?= htmlspecialchars($formData['justification'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" 
                                      placeholder="Any additional information about this retroactive documentation"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Supporting Documents -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-paperclip me-2"></i>Supporting Documents
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Important:</strong> For retroactive POs, please attach all available documentation including purchase receipts, invoices, and any evidence of the transaction.
                        </div>
                        
                        <div class="mb-3">
                            <label for="purchase_receipt_file" class="form-label">Purchase Receipt/Invoice <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="purchase_receipt_file" name="purchase_receipt_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <div class="form-text">Upload purchase receipt or sales invoice (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quote_file" class="form-label">Vendor Quotation</label>
                            <input type="file" class="form-control" id="quote_file" name="quote_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">Upload vendor quotation or price list if available (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="supporting_evidence_file" class="form-label">Additional Evidence</label>
                            <input type="file" class="form-control" id="supporting_evidence_file" name="supporting_evidence_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">Upload any additional supporting documentation (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</div>
                        </div>
                        
                        <!-- Quotation Reference Fields -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quotation_number" class="form-label">Quotation Number</label>
                                <input type="text" name="quotation_number" id="quotation_number" class="form-control" 
                                       placeholder="e.g. QUO-2024-001"
                                       value="<?= htmlspecialchars($formData['quotation_number'] ?? '') ?>">
                                <div class="form-text">Vendor's quotation reference number</div>
                            </div>
                            <div class="col-md-6">
                                <label for="quotation_date" class="form-label">Quotation Date</label>
                                <input type="date" name="quotation_date" id="quotation_date" class="form-control"
                                       value="<?= htmlspecialchars($formData['quotation_date'] ?? '') ?>">
                                <div class="form-text">Date of vendor quotation</div>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <label for="file_upload_notes" class="form-label">Document Notes</label>
                            <textarea name="file_upload_notes" id="file_upload_notes" class="form-control" rows="2"
                                      placeholder="Add notes about the uploaded documents"><?= htmlspecialchars($formData['file_upload_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Financial Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-calculator me-2"></i>Financial Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="vat_rate" class="form-label">VAT Rate (%)</label>
                                <input type="number" name="vat_rate" id="vat_rate" class="form-control" 
                                       value="<?= htmlspecialchars($formData['vat_rate'] ?? '12.00') ?>" 
                                       step="0.01" min="0" max="100">
                            </div>
                            <div class="col-6 mb-3">
                                <label for="ewt_rate" class="form-label">EWT Rate (%)</label>
                                <input type="number" name="ewt_rate" id="ewt_rate" class="form-control" 
                                       value="<?= htmlspecialchars($formData['ewt_rate'] ?? '1.00') ?>" 
                                       step="0.01" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="handling_fee" class="form-label">Handling Fee</label>
                            <input type="number" name="handling_fee" id="handling_fee" class="form-control" 
                                   value="<?= htmlspecialchars($formData['handling_fee'] ?? '0') ?>" 
                                   step="0.01" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="discount_amount" class="form-label">Discount Amount</label>
                            <input type="number" name="discount_amount" id="discount_amount" class="form-control" 
                                   value="<?= htmlspecialchars($formData['discount_amount'] ?? '0') ?>" 
                                   step="0.01" min="0">
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount:</strong>
                            <strong id="totalAmount">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-check-circle me-1"></i>Create Retroactive PO
                        </button>
                        <a href="?route=procurement-orders" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            This will create a PO for documentation purposes and integrate with existing workflows.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- JavaScript for form functionality -->
    <script>
        let itemCount = 0;

        function addItem() {
            itemCount++;
            const itemHtml = `
                <div class="border rounded p-3 mb-3" id="item${itemCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Item #${itemCount}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${itemCount})">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="items[${itemCount}][item_name]" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Category</label>
                            <select name="items[${itemCount}][category_id]" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemCount}][quantity]" class="form-control quantity-input" min="1" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="items[${itemCount}][unit]" class="form-control" value="pcs">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemCount}][unit_price]" class="form-control unit-price-input" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Qty Received</label>
                            <input type="number" name="items[${itemCount}][quantity_received]" class="form-control" min="0">
                            <div class="form-text">If already received</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="items[${itemCount}][description]" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="items[${itemCount}][brand]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input type="text" name="items[${itemCount}][model]" class="form-control">
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('itemsList').insertAdjacentHTML('beforeend', itemHtml);
            updateTotalAmount();
        }

        function removeItem(itemId) {
            document.getElementById('item' + itemId).remove();
            updateTotalAmount();
        }

        function updateTotalAmount() {
            let total = 0;
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const priceInputs = document.querySelectorAll('.unit-price-input');
            
            quantityInputs.forEach((qtyInput, index) => {
                const quantity = parseFloat(qtyInput.value) || 0;
                const price = parseFloat(priceInputs[index].value) || 0;
                total += quantity * price;
            });
            
            const vatRate = parseFloat(document.getElementById('vat_rate').value) || 0;
            const ewtRate = parseFloat(document.getElementById('ewt_rate').value) || 0;
            const handlingFee = parseFloat(document.getElementById('handling_fee').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
            
            const vatAmount = total * (vatRate / 100);
            const ewtAmount = total * (ewtRate / 100);
            const finalTotal = total + vatAmount - ewtAmount + handlingFee - discountAmount;
            
            document.getElementById('totalAmount').textContent = '₱' + finalTotal.toFixed(2);
        }

        // Add initial item
        document.addEventListener('DOMContentLoaded', function() {
            addItem();
            
            // Add event listeners for real-time calculation
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('quantity-input') || 
                    e.target.classList.contains('unit-price-input') ||
                    e.target.id === 'vat_rate' || 
                    e.target.id === 'ewt_rate' || 
                    e.target.id === 'handling_fee' || 
                    e.target.id === 'discount_amount') {
                    updateTotalAmount();
                }
            });
        });
    </script>

<?php else: ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Access Denied:</strong> You do not have permission to create procurement orders.
    </div>
<?php endif; ?>

<?php
// Store the captured content
$content = ob_get_clean();

// Include the main layout
include APP_ROOT . '/views/layouts/main.php';
?>