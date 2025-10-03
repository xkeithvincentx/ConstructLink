<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';

$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<style>
/* Business Category Styling */
.category-business-info {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
}

.category-business-info .badge {
    font-size: 0.75rem;
}

.category-select option.asset-generating {
    background-color: #d4edda;
    color: #155724;
}

.category-select option.direct-expense {
    background-color: #fff3cd;
    color: #856404;
}

/* Enhanced tooltips */
[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Business info badges styling */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}
</style>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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
    <form method="POST" action="?route=procurement-orders/create" id="procurementOrderForm" enctype="multipart/form-data">
        <?= CSRFProtection::getTokenField() ?>
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
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
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control" 
                                   placeholder="Brief description of procurement order"
                                   value="<?= htmlspecialchars($formData['title'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="package_scope" class="form-label">Package Scope</label>
                                <textarea name="package_scope" id="package_scope" class="form-control" rows="3"
                                          placeholder="Describe the scope of this procurement package"><?= htmlspecialchars($formData['package_scope'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="work_breakdown" class="form-label">Work Breakdown</label>
                                <textarea name="work_breakdown" id="work_breakdown" class="form-control" rows="3"
                                          placeholder="Breakdown of work or deliverables"><?= htmlspecialchars($formData['work_breakdown'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="budget_allocation" class="form-label">Budget Allocation</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="budget_allocation" id="budget_allocation" class="form-control" 
                                           step="0.01" min="0" placeholder="0.00"
                                           value="<?= htmlspecialchars($formData['budget_allocation'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_needed" class="form-label">Date Needed</label>
                                <input type="date" name="date_needed" id="date_needed" class="form-control"
                                       value="<?= htmlspecialchars($formData['date_needed'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <!-- Delivery Information -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="delivery_method" class="form-label">
                                    <span id="delivery-method-label">Preferred Delivery Method</span>
                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                       id="delivery-method-tooltip" title="Options will update based on selected item categories"></i>
                                </label>
                                <select name="delivery_method" id="delivery_method" class="form-select">
                                    <option value="">Select Delivery Method</option>
                                    <!-- Physical Delivery Options -->
                                    <optgroup id="physical-delivery-group" label="Physical Delivery">
                                        <option value="Pickup" <?= ($formData['delivery_method'] ?? '') == 'Pickup' ? 'selected' : '' ?>>Pickup</option>
                                        <option value="Direct Delivery" <?= ($formData['delivery_method'] ?? '') == 'Direct Delivery' ? 'selected' : '' ?>>Direct Delivery</option>
                                        <option value="Batch Delivery" <?= ($formData['delivery_method'] ?? '') == 'Batch Delivery' ? 'selected' : '' ?>>Batch Delivery</option>
                                        <option value="Airfreight" <?= ($formData['delivery_method'] ?? '') == 'Airfreight' ? 'selected' : '' ?>>Airfreight</option>
                                        <option value="Bus Cargo" <?= ($formData['delivery_method'] ?? '') == 'Bus Cargo' ? 'selected' : '' ?>>Bus Cargo</option>
                                        <option value="Courier" <?= ($formData['delivery_method'] ?? '') == 'Courier' ? 'selected' : '' ?>>Courier</option>
                                    </optgroup>
                                    <!-- Service Delivery Options -->
                                    <optgroup id="service-delivery-group" label="Service Delivery" style="display: none;">
                                        <option value="On-site Service" <?= ($formData['delivery_method'] ?? '') == 'On-site Service' ? 'selected' : '' ?>>On-site Service</option>
                                        <option value="Remote Service" <?= ($formData['delivery_method'] ?? '') == 'Remote Service' ? 'selected' : '' ?>>Remote Service</option>
                                        <option value="Digital Delivery" <?= ($formData['delivery_method'] ?? '') == 'Digital Delivery' ? 'selected' : '' ?>>Digital Delivery</option>
                                        <option value="Email Delivery" <?= ($formData['delivery_method'] ?? '') == 'Email Delivery' ? 'selected' : '' ?>>Email Delivery</option>
                                        <option value="Postal Mail" <?= ($formData['delivery_method'] ?? '') == 'Postal Mail' ? 'selected' : '' ?>>Postal Mail</option>
                                        <option value="Office Pickup" <?= ($formData['delivery_method'] ?? '') == 'Office Pickup' ? 'selected' : '' ?>>Office Pickup</option>
                                        <option value="Service Completion" <?= ($formData['delivery_method'] ?? '') == 'Service Completion' ? 'selected' : '' ?>>Service Completion</option>
                                        <option value="N/A" <?= ($formData['delivery_method'] ?? '') == 'N/A' ? 'selected' : '' ?>>N/A</option>
                                    </optgroup>
                                </select>
                                <div class="form-text" id="delivery-method-help">
                                    Select preferred delivery method for inclusion in terms and conditions
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="delivery_location" class="form-label">
                                    <span id="delivery-location-label">Delivery Location</span>
                                    <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                       id="delivery-location-tooltip" title="Options will update based on selected item categories"></i>
                                </label>
                                <select name="delivery_location" id="delivery_location" class="form-select">
                                    <option value="">Select Delivery Location</option>
                                    <!-- Physical Location Options -->
                                    <optgroup id="physical-location-group" label="Physical Locations">
                                        <option value="Project Site" <?= ($formData['delivery_location'] ?? '') == 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                        <option value="Main Office" <?= ($formData['delivery_location'] ?? '') == 'Main Office' ? 'selected' : '' ?>>Main Office</option>
                                        <option value="Branch Office" <?= ($formData['delivery_location'] ?? '') == 'Branch Office' ? 'selected' : '' ?>>Branch Office</option>
                                        <option value="Warehouse" <?= ($formData['delivery_location'] ?? '') == 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                        <option value="Other" <?= ($formData['delivery_location'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </optgroup>
                                    <!-- Service Location Options -->
                                    <optgroup id="service-location-group" label="Service Locations" style="display: none;">
                                        <option value="Project Site" <?= ($formData['delivery_location'] ?? '') == 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                        <option value="Client Office" <?= ($formData['delivery_location'] ?? '') == 'Client Office' ? 'selected' : '' ?>>Client Office</option>
                                        <option value="Service Provider Office" <?= ($formData['delivery_location'] ?? '') == 'Service Provider Office' ? 'selected' : '' ?>>Service Provider Office</option>
                                        <option value="Digital/Email" <?= ($formData['delivery_location'] ?? '') == 'Digital/Email' ? 'selected' : '' ?>>Digital/Email</option>
                                        <option value="Multiple Locations" <?= ($formData['delivery_location'] ?? '') == 'Multiple Locations' ? 'selected' : '' ?>>Multiple Locations</option>
                                        <option value="N/A" <?= ($formData['delivery_location'] ?? '') == 'N/A' ? 'selected' : '' ?>>N/A</option>
                                    </optgroup>
                                </select>
                                <div class="form-text" id="delivery-location-help">
                                    Specify where items should be delivered
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery Category Summary -->
                        <div id="delivery-category-summary" class="alert alert-info" style="display: none;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Mixed Category Order</strong>
                            </div>
                            <div id="delivery-summary-content">
                                <!-- Dynamic content based on selected categories -->
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="justification" class="form-label">Justification</label>
                            <textarea name="justification" id="justification" class="form-control" rows="3"
                                      placeholder="Justify the need for this procurement"><?= htmlspecialchars($formData['justification'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Items <span class="text-danger">*</span>
                        </h6>
                        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                <i class="bi bi-plus-circle me-1"></i>Add Item
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div id="itemsContainer">
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <div class="item-row border rounded p-3 mb-3" data-index="<?= $index ?>">
                                        <!-- Item form fields here -->
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Default empty item -->
                                <div class="item-row border rounded p-3 mb-3" data-index="0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Item #1</h6>
                                        <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)" style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                            <input type="text" name="items[0][item_name]" class="form-control" 
                                                   placeholder="Enter item name" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Category 
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                                                   title="Category determines how items will be processed: Asset generation vs Direct expense"></i>
                                            </label>
                                            <select name="items[0][category_id]" class="form-select category-select" 
                                                    onchange="updateQuantityField(this); showCategoryBusinessInfo(this); updateDeliveryOptions()">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): 
                                                    $businessIcon = '';
                                                    $businessClass = '';
                                                    if (isset($category['generates_assets'])) {
                                                        if ($category['generates_assets']) {
                                                            $businessIcon = $category['asset_type'] === 'capital' ? '🔧' : 
                                                                          ($category['asset_type'] === 'inventory' ? '📦' : '💼');
                                                            $businessClass = 'asset-generating';
                                                        } else {
                                                            $businessIcon = '💰';
                                                            $businessClass = 'direct-expense';
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $category['id'] ?>" 
                                                            data-consumable="<?= $category['is_consumable'] ?>"
                                                            data-generates-assets="<?= ($category['generates_assets'] ?? 1) ? 'true' : 'false' ?>"
                                                            data-asset-type="<?= htmlspecialchars($category['asset_type'] ?? 'capital') ?>"
                                                            data-expense-category="<?= htmlspecialchars($category['expense_category'] ?? '') ?>"
                                                            data-business-description="<?= htmlspecialchars($category['business_description'] ?? '') ?>"
                                                            class="<?= $businessClass ?>">
                                                        <?= $businessIcon ?> <?= htmlspecialchars($category['name']) ?>
                                                        <?php if (isset($category['generates_assets']) && !$category['generates_assets']): ?>
                                                            <small>(Expense)</small>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="category-business-info-0" class="category-business-info mt-2 p-2 border rounded" style="display: none;">
                                                <!-- Dynamic category business information will be inserted here -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Brand</label>
                                            <input type="text" name="items[0][brand]" class="form-control" 
                                                   placeholder="Brand name">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Model</label>
                                            <input type="text" name="items[0][model]" class="form-control" 
                                                   placeholder="Model number">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" name="items[0][quantity]" class="form-control quantity-input" 
                                                   min="1" value="1" required onchange="calculateItemTotal(this)">
                                            <div class="form-text quantity-help">
                                                <span class="quantity-consumable-text" style="display: none;">
                                                    <i class="bi bi-info-circle me-1"></i>Variable quantity for consumable items
                                                </span>
                                        <span class="quantity-non-consumable-text">
                                            <i class="bi bi-info-circle me-1"></i>Specify quantity to order (individual assets will be created)
                                        </span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Unit</label>
                                            <input type="text" name="items[0][unit]" class="form-control" 
                                                   value="pcs" placeholder="Unit">
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="items[0][unit_price]" class="form-control unit-price-input" 
                                                       step="0.01" min="0" required onchange="calculateItemTotal(this)">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Subtotal</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="text" class="form-control item-subtotal" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="items[0][description]" class="form-control" rows="2"
                                                  placeholder="Item description"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specifications</label>
                                        <textarea name="items[0][specifications]" class="form-control" rows="2"
                                                  placeholder="Technical specifications"></textarea>
                                    </div>
                                    
                                    <div class="mb-0">
                                        <label class="form-label">Notes</label>
                                        <textarea name="items[0][item_notes]" class="form-control" rows="2"
                                                  placeholder="Additional notes for this item"></textarea>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center">
                            <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                                <button type="button" class="btn btn-outline-primary" onclick="addItem()">
                                    <i class="bi bi-plus-circle me-1"></i>Add Another Item
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-calculator me-2"></i>Financial Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="vat_rate" class="form-label">VAT Rate (%)</label>
                                <input type="number" name="vat_rate" id="vat_rate" class="form-control" 
                                       step="0.01" min="0" max="100" value="<?= htmlspecialchars($formData['vat_rate'] ?? '12.00') ?>"
                                       onchange="calculateTotals()">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="ewt_rate" class="form-label">EWT Rate (%)</label>
                                <input type="number" name="ewt_rate" id="ewt_rate" class="form-control" 
                                       step="0.01" min="0" max="100" value="<?= htmlspecialchars($formData['ewt_rate'] ?? '2.00') ?>"
                                       onchange="calculateTotals()">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="handling_fee" class="form-label">Handling Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="handling_fee" id="handling_fee" class="form-control" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['handling_fee'] ?? '0') ?>"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="discount_amount" class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="discount_amount" id="discount_amount" class="form-control" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['discount_amount'] ?? '0') ?>"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-chat-text me-2"></i>Additional Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea name="notes" id="notes" class="form-control" rows="4"
                                  placeholder="Any additional notes or special instructions"><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- File Attachments -->
                <?php
                // Get allowed file types for regular PO creation (not retroactive, Draft status)
                require_once APP_ROOT . '/models/ProcurementOrderModel.php';
                $allowedFileTypes = ProcurementOrderModel::getAllowedFileTypes(false, 'Draft');
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-paperclip me-2"></i>File Attachments
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Pre-Purchase Documentation:</strong> At this stage, only vendor quotations are relevant since the purchase hasn't been made yet. Purchase receipts and supporting documents can be added after delivery completion.
                        </div>
                        
                        <?php foreach ($allowedFileTypes as $fileType => $config): ?>
                            <?php if ($config['allowed']): ?>
                            <div class="mb-3">
                                <label for="<?= $fileType ?>" class="form-label">
                                    <?= $config['label'] ?>
                                    <?php if ($config['required']): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="<?= $fileType ?>" 
                                       name="<?= $fileType ?>" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       <?= $config['required'] ? 'required' : '' ?>>
                                <div class="form-text"><?= htmlspecialchars($config['help']) ?> (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
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
                                      placeholder="Add notes about the uploaded quotation or other pre-purchase documentation"><?= htmlspecialchars($formData['file_upload_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 1rem;">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-receipt me-2"></i>Order Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="summary-subtotal">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>VAT (<span id="summary-vat-rate">12.00</span>%):</span>
                            <span id="summary-vat">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>EWT (<span id="summary-ewt-rate">2.00</span>%):</span>
                            <span id="summary-ewt" class="text-danger">-₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Handling Fee:</span>
                            <span id="summary-handling">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span id="summary-discount" class="text-success">-₱0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Net Total:</span>
                            <span id="summary-total">₱0.00</span>
                        </div>
                        
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Total Items:</span>
                                <span id="summary-item-count">0</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Total Quantity:</span>
                                <span id="summary-quantity">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>Create Procurement Order
                                </button>
                            <?php endif; ?>
                            <a href="?route=procurement-orders" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<script>
let itemIndex = 1;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemCount = container.children.length + 1;
    
    const itemHtml = `
        <div class="item-row border rounded p-3 mb-3" data-index="${itemIndex}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Item #${itemCount}</h6>
                <?php if (in_array($user['role_name'], $roleConfig['procurement-orders/create'] ?? [])): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="items[${itemIndex}][item_name]" class="form-control" 
                           placeholder="Enter item name" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Category 
                        <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" 
                           title="Category determines how items will be processed: Asset generation vs Direct expense"></i>
                    </label>
                    <select name="items[${itemIndex}][category_id]" class="form-select category-select" 
                            onchange="updateQuantityField(this); showCategoryBusinessInfo(this); updateDeliveryOptions()">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): 
                            $businessIcon = '';
                            $businessClass = '';
                            if (isset($category['generates_assets'])) {
                                if ($category['generates_assets']) {
                                    $businessIcon = $category['asset_type'] === 'capital' ? '🔧' : 
                                                  ($category['asset_type'] === 'inventory' ? '📦' : '💼');
                                    $businessClass = 'asset-generating';
                                } else {
                                    $businessIcon = '💰';
                                    $businessClass = 'direct-expense';
                                }
                            }
                        ?>
                            <option value="<?= $category['id'] ?>" 
                                    data-consumable="<?= $category['is_consumable'] ?>"
                                    data-generates-assets="<?= ($category['generates_assets'] ?? 1) ? 'true' : 'false' ?>"
                                    data-asset-type="<?= htmlspecialchars($category['asset_type'] ?? 'capital') ?>"
                                    data-expense-category="<?= htmlspecialchars($category['expense_category'] ?? '') ?>"
                                    data-business-description="<?= htmlspecialchars($category['business_description'] ?? '') ?>"
                                    class="<?= $businessClass ?>">
                                <?= $businessIcon ?> <?= htmlspecialchars($category['name']) ?>
                                <?php if (isset($category['generates_assets']) && !$category['generates_assets']): ?>
                                    <small>(Expense)</small>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="category-business-info-${itemIndex}" class="category-business-info mt-2 p-2 border rounded" style="display: none;">
                        <!-- Dynamic category business information will be inserted here -->
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" name="items[${itemIndex}][brand]" class="form-control" 
                           placeholder="Brand name">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Model</label>
                    <input type="text" name="items[${itemIndex}][model]" class="form-control" 
                           placeholder="Model number">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity-input" 
                           min="1" value="1" required onchange="calculateItemTotal(this)">
                    <div class="form-text quantity-help">
                        <span class="quantity-consumable-text" style="display: none;">
                            <i class="bi bi-info-circle me-1"></i>Variable quantity for consumable items
                        </span>
                        <span class="quantity-non-consumable-text">
                            <i class="bi bi-info-circle me-1"></i>Specify quantity to order (individual assets will be created)
                        </span>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Unit</label>
                    <input type="text" name="items[${itemIndex}][unit]" class="form-control" 
                           value="pcs" placeholder="Unit">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="items[${itemIndex}][unit_price]" class="form-control unit-price-input" 
                               step="0.01" min="0" required onchange="calculateItemTotal(this)">
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Subtotal</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="text" class="form-control item-subtotal" readonly>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="items[${itemIndex}][description]" class="form-control" rows="2"
                          placeholder="Item description"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Specifications</label>
                <textarea name="items[${itemIndex}][specifications]" class="form-control" rows="2"
                          placeholder="Technical specifications"></textarea>
            </div>
            
            <div class="mb-0">
                <label class="form-label">Notes</label>
                <textarea name="items[${itemIndex}][item_notes]" class="form-control" rows="2"
                          placeholder="Additional notes for this item"></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
    itemIndex++;
    
    // Show remove buttons if more than one item
    updateRemoveButtons();
    calculateTotals();
}

function removeItem(button) {
    const itemRow = button.closest('.item-row');
    itemRow.remove();
    
    // Update item numbers
    updateItemNumbers();
    updateRemoveButtons();
    calculateTotals();
}

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((item, index) => {
        const title = item.querySelector('h6');
        title.textContent = `Item #${index + 1}`;
    });
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.item-row');
    const removeButtons = document.querySelectorAll('.item-row .btn-outline-danger');
    
    removeButtons.forEach(button => {
        button.style.display = items.length > 1 ? 'inline-block' : 'none';
    });
}

function updateQuantityField(categorySelect) {
    const itemRow = categorySelect.closest('.item-row');
    const quantityInput = itemRow.querySelector('.quantity-input');
    const quantityConsumableText = itemRow.querySelector('.quantity-consumable-text');
    const quantityNonConsumableText = itemRow.querySelector('.quantity-non-consumable-text');
    
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const isConsumable = selectedOption.getAttribute('data-consumable') == '1';
    
    if (categorySelect.value && isConsumable) {
        // Enable quantity input for consumables
        quantityInput.disabled = false;
        quantityInput.min = 1;
        quantityConsumableText.style.display = 'inline';
        quantityNonConsumableText.style.display = 'none';
    } else if (categorySelect.value && !isConsumable) {
        // Enable quantity input for non-consumables too - users can order multiple units
        quantityInput.disabled = false;
        quantityInput.min = 1;
        quantityConsumableText.style.display = 'none';
        quantityNonConsumableText.style.display = 'inline';
    } else {
        // No category selected, default behavior
        quantityInput.disabled = false;
        quantityConsumableText.style.display = 'none';
        quantityNonConsumableText.style.display = 'inline';
    }
    
    // Recalculate totals after quantity change
    calculateItemTotal(quantityInput);
}

function showCategoryBusinessInfo(categorySelect) {
    const itemRow = categorySelect.closest('.item-row');
    const itemIndex = Array.from(document.querySelectorAll('.item-row')).indexOf(itemRow);
    const infoDiv = document.getElementById(`category-business-info-${itemIndex}`);
    
    if (!infoDiv) {
        return; // Info div not found
    }
    
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    
    if (!categorySelect.value) {
        infoDiv.style.display = 'none';
        return;
    }
    
    const generatesAssets = selectedOption.getAttribute('data-generates-assets') === 'true';
    const assetType = selectedOption.getAttribute('data-asset-type');
    const expenseCategory = selectedOption.getAttribute('data-expense-category');
    const businessDescription = selectedOption.getAttribute('data-business-description');
    const isConsumable = selectedOption.getAttribute('data-consumable') == '1';
    
    let infoHtml = '<div class="d-flex align-items-center mb-2">';
    
    if (generatesAssets) {
        const typeDisplay = assetType === 'capital' ? 'Capital Asset' : 
                          (assetType === 'inventory' ? 'Inventory/Materials' : 'Asset');
        infoHtml += `
            <div class="badge bg-success me-2">
                <i class="bi bi-check-circle me-1"></i>Asset Generating
            </div>
            <small class="text-muted">${typeDisplay}</small>
        `;
        
        if (isConsumable) {
            infoHtml += `
                <div class="badge bg-info ms-2">
                    <i class="bi bi-arrow-repeat me-1"></i>Consumable
                </div>
            `;
        }
    } else {
        const expenseDisplay = expenseCategory === 'professional_services' ? 'Professional Services' :
                             (expenseCategory === 'maintenance' ? 'Maintenance & Repair' :
                             (expenseCategory === 'operating' ? 'Operating Expenses' :
                             (expenseCategory === 'regulatory' ? 'Regulatory & Compliance' : 'Direct Expense')));
        infoHtml += `
            <div class="badge bg-warning text-dark me-2">
                <i class="bi bi-cash me-1"></i>Direct Expense
            </div>
            <small class="text-muted">${expenseDisplay}</small>
        `;
    }
    
    infoHtml += '</div>';
    
    if (businessDescription) {
        infoHtml += `<small class="text-muted d-block">${businessDescription}</small>`;
    }
    
    // Add processing info
    if (generatesAssets) {
        infoHtml += `
            <div class="mt-2">
                <small class="text-success">
                    <i class="bi bi-info-circle me-1"></i>
                    This item will create trackable assets after receipt and can be assigned to projects.
                </small>
            </div>
        `;
    } else {
        infoHtml += `
            <div class="mt-2">
                <small class="text-warning">
                    <i class="bi bi-info-circle me-1"></i>
                    This item will be expensed directly and allocated to project costs.
                </small>
            </div>
        `;
    }
    
    infoDiv.innerHTML = infoHtml;
    infoDiv.style.display = 'block';
}

function calculateItemTotal(input) {
    const itemRow = input.closest('.item-row');
    const quantity = parseFloat(itemRow.querySelector('.quantity-input').value) || 0;
    const unitPrice = parseFloat(itemRow.querySelector('.unit-price-input').value) || 0;
    const subtotal = quantity * unitPrice;
    
    itemRow.querySelector('.item-subtotal').value = subtotal.toFixed(2);
    
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalQuantity = 0;
    let itemCount = 0;
    
    // Calculate subtotal from all items
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price-input').value) || 0;
        const itemSubtotal = quantity * unitPrice;
        
        subtotal += itemSubtotal;
        totalQuantity += quantity;
        itemCount++;
    });
    
    // Get rates and fees
    const vatRate = parseFloat(document.getElementById('vat_rate').value) || 0;
    const ewtRate = parseFloat(document.getElementById('ewt_rate').value) || 0;
    const handlingFee = parseFloat(document.getElementById('handling_fee').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    // Calculate amounts
    const vatAmount = subtotal * (vatRate / 100);
    const ewtAmount = subtotal * (ewtRate / 100);
    const netTotal = subtotal + vatAmount - ewtAmount + handlingFee - discountAmount;
    
    // Update summary
    document.getElementById('summary-subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('summary-vat-rate').textContent = vatRate.toFixed(2);
    document.getElementById('summary-vat').textContent = '₱' + vatAmount.toFixed(2);
    document.getElementById('summary-ewt-rate').textContent = ewtRate.toFixed(2);
    document.getElementById('summary-ewt').textContent = '-₱' + ewtAmount.toFixed(2);
    document.getElementById('summary-handling').textContent = '₱' + handlingFee.toFixed(2);
    document.getElementById('summary-discount').textContent = '-₱' + discountAmount.toFixed(2);
    document.getElementById('summary-total').textContent = '₱' + netTotal.toFixed(2);
    document.getElementById('summary-item-count').textContent = itemCount;
    document.getElementById('summary-quantity').textContent = totalQuantity;
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    updateRemoveButtons();
    calculateTotals();
    
    // Add event listeners to financial inputs
    ['vat_rate', 'ewt_rate', 'handling_fee', 'discount_amount'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateTotals);
    });
    
    // Initialize quantity fields for existing items
    document.querySelectorAll('.category-select').forEach(select => {
        updateQuantityField(select);
        showCategoryBusinessInfo(select);
    });
    
    // Initialize delivery options based on categories
    updateDeliveryOptions();
});

// Update delivery options based on selected categories
function updateDeliveryOptions() {
    const categorySelects = document.querySelectorAll('.category-select');
    const physicalGroup = document.getElementById('physical-delivery-group');
    const serviceGroup = document.getElementById('service-delivery-group');
    const physicalLocationGroup = document.getElementById('physical-location-group');
    const serviceLocationGroup = document.getElementById('service-location-group');
    const deliverySummary = document.getElementById('delivery-category-summary');
    const summaryContent = document.getElementById('delivery-summary-content');
    
    let hasPhysicalItems = false;
    let hasServiceItems = false;
    const categoryTypes = [];
    
    // Analyze selected categories
    categorySelects.forEach(select => {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const generatesAssets = selectedOption.getAttribute('data-generates-assets') === 'true';
            const assetType = selectedOption.getAttribute('data-asset-type');
            const expenseCategory = selectedOption.getAttribute('data-expense-category');
            
            if (generatesAssets) {
                hasPhysicalItems = true;
                categoryTypes.push({
                    type: 'physical',
                    assetType: assetType,
                    name: selectedOption.text
                });
            } else {
                hasServiceItems = true;
                categoryTypes.push({
                    type: 'service',
                    expenseCategory: expenseCategory,
                    name: selectedOption.text
                });
            }
        }
    });
    
    // Update delivery method options
    if (hasPhysicalItems && hasServiceItems) {
        // Mixed order - show both groups
        physicalGroup.style.display = 'block';
        serviceGroup.style.display = 'block';
        physicalLocationGroup.style.display = 'block';
        serviceLocationGroup.style.display = 'block';
        
        // Update labels and help text
        document.getElementById('delivery-method-label').textContent = 'Delivery/Service Method';
        document.getElementById('delivery-location-label').textContent = 'Delivery/Service Location';
        document.getElementById('delivery-method-help').textContent = 'Select method for physical delivery or service completion';
        document.getElementById('delivery-location-help').textContent = 'Specify location for physical delivery or service provision';
        
        // Show summary
        deliverySummary.style.display = 'block';
        const physicalTypes = categoryTypes.filter(c => c.type === 'physical');
        const serviceTypes = categoryTypes.filter(c => c.type === 'service');
        
        summaryContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>🔧 Physical Items:</strong>
                    <ul class="mb-0 mt-1">
                        ${physicalTypes.map(c => `<li><small>${c.name} (${c.assetType})</small></li>`).join('')}
                    </ul>
                </div>
                <div class="col-md-6">
                    <strong>💰 Service Items:</strong>
                    <ul class="mb-0 mt-1">
                        ${serviceTypes.map(c => `<li><small>${c.name} (${c.expenseCategory})</small></li>`).join('')}
                    </ul>
                </div>
            </div>
            <div class="mt-2 text-muted small">
                <i class="bi bi-lightbulb me-1"></i>This order contains both physical items and services. 
                Delivery options include both physical delivery and service completion methods.
            </div>
        `;
        
    } else if (hasServiceItems) {
        // Service-only order
        physicalGroup.style.display = 'none';
        serviceGroup.style.display = 'block';
        physicalLocationGroup.style.display = 'none';
        serviceLocationGroup.style.display = 'block';
        
        // Update labels and help text
        document.getElementById('delivery-method-label').textContent = 'Service Delivery Method';
        document.getElementById('delivery-location-label').textContent = 'Service Location';
        document.getElementById('delivery-method-help').textContent = 'Select how services will be delivered or completed';
        document.getElementById('delivery-location-help').textContent = 'Specify where services will be performed';
        
        // Hide summary for pure service orders
        deliverySummary.style.display = 'none';
        
    } else {
        // Physical-only order or no items selected yet
        physicalGroup.style.display = 'block';
        serviceGroup.style.display = 'none';
        physicalLocationGroup.style.display = 'block';
        serviceLocationGroup.style.display = 'none';
        
        // Update labels and help text
        document.getElementById('delivery-method-label').textContent = 'Preferred Delivery Method';
        document.getElementById('delivery-location-label').textContent = 'Delivery Location';
        document.getElementById('delivery-method-help').textContent = 'Select preferred delivery method for inclusion in terms and conditions';
        document.getElementById('delivery-location-help').textContent = 'Specify where items should be delivered';
        
        // Hide summary for pure physical orders
        deliverySummary.style.display = 'none';
    }
    
    // Clear invalid selections when switching modes
    const deliveryMethodSelect = document.getElementById('delivery_method');
    const deliveryLocationSelect = document.getElementById('delivery_location');
    
    if (deliveryMethodSelect.value) {
        const selectedMethodOption = Array.from(deliveryMethodSelect.options).find(option => 
            option.value === deliveryMethodSelect.value && option.offsetParent !== null
        );
        if (!selectedMethodOption) {
            deliveryMethodSelect.value = '';
        }
    }
    
    if (deliveryLocationSelect.value) {
        const selectedLocationOption = Array.from(deliveryLocationSelect.options).find(option => 
            option.value === deliveryLocationSelect.value && option.offsetParent !== null
        );
        if (!selectedLocationOption) {
            deliveryLocationSelect.value = '';
        }
    }
}

// Form validation
document.getElementById('procurementOrderForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('.item-row');
    if (items.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the procurement order.');
        return false;
    }
    
    let hasValidItem = false;
    items.forEach(item => {
        const itemName = item.querySelector('input[name*="[item_name]"]').value.trim();
        const quantity = item.querySelector('input[name*="[quantity]"]').value;
        const unitPrice = item.querySelector('input[name*="[unit_price]"]').value;
        
        if (itemName && quantity && unitPrice) {
            hasValidItem = true;
        }
    });
    
    if (!hasValidItem) {
        e.preventDefault();
        alert('Please ensure at least one item has a name, quantity, and unit price.');
        return false;
    }
});
</script>
<?php else: ?>
<div class="alert alert-danger mt-4">You do not have permission to create a procurement order.</div>
<?php endif; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Procurement Order - ConstructLink™';
$pageHeader = 'Create Procurement Order';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'Create Order', 'url' => '?route=procurement-orders/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
