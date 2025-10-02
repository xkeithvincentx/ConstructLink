<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
$canEdit = in_array($user['role_name'], $roleConfig['procurement-orders/edit'] ?? []) && in_array($procurementOrder['status'], ['Draft', 'Pending']);
?>
<?php if ($canEdit): ?>
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil me-2"></i>
        Edit Procurement Order #<?= htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']) ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order
        </a>
    </div>
</div>

<!-- Error Messages -->
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
    <!-- Main Form -->
    <div class="col-lg-8">
        <form method="POST" action="?route=procurement-orders/edit&id=<?= $procurementOrder['id'] ?>" id="procurementOrderForm" enctype="multipart/form-data">
            <?= CSRFProtection::getTokenField() ?>
                <!-- Order Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Order Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="po_number" class="form-label">PO Number</label>
                                    <input type="text" class="form-control" id="po_number" name="po_number" 
                                           value="<?= htmlspecialchars($formData['po_number'] ?? $procurementOrder['po_number'] ?? '') ?>"
                                           placeholder="Leave blank for auto-generation">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">Select Project</option>
                                        <?php if (isset($projects)): ?>
                                            <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" 
                                                    <?= ($formData['project_id'] ?? $procurementOrder['project_id']) == $project['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($project['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                    <select class="form-select" id="vendor_id" name="vendor_id" required>
                                        <option value="">Select Vendor</option>
                                        <?php if (isset($vendors)): ?>
                                            <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= $vendor['id'] ?>" 
                                                    <?= ($formData['vendor_id'] ?? $procurementOrder['vendor_id']) == $vendor['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($vendor['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_needed" class="form-label">Date Needed</label>
                                    <input type="date" class="form-control" id="date_needed" name="date_needed" 
                                           value="<?= htmlspecialchars($formData['date_needed'] ?? $procurementOrder['date_needed'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_method" class="form-label">Preferred Delivery Method</label>
                                    <select class="form-select" id="delivery_method" name="delivery_method">
                                        <option value="">Select Method</option>
                                        <option value="Pickup" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Pickup' ? 'selected' : '' ?>>Pickup</option>
                                        <option value="Direct Delivery" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Direct Delivery' ? 'selected' : '' ?>>Direct Delivery</option>
                                        <option value="Batch Delivery" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Batch Delivery' ? 'selected' : '' ?>>Batch Delivery</option>
                                        <option value="Airfreight" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Airfreight' ? 'selected' : '' ?>>Airfreight</option>
                                        <option value="Bus Cargo" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Bus Cargo' ? 'selected' : '' ?>>Bus Cargo</option>
                                        <option value="Courier" <?= ($formData['delivery_method'] ?? $procurementOrder['delivery_method'] ?? '') == 'Courier' ? 'selected' : '' ?>>Courier</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select preferred delivery method for inclusion in terms and conditions
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="delivery_location" class="form-label">
                                        <span id="delivery-location-label">Delivery Location</span>
                                        <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                           id="delivery-location-tooltip" title="Options are based on selected item categories"></i>
                                    </label>
                                    <select class="form-select" id="delivery_location" name="delivery_location">
                                        <option value="">Select Location</option>
                                        <!-- Physical Location Options -->
                                        <optgroup id="physical-location-group" label="Physical Locations">
                                            <option value="Project Site" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                            <option value="Main Office" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Main Office' ? 'selected' : '' ?>>Main Office</option>
                                            <option value="Branch Office" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Branch Office' ? 'selected' : '' ?>>Branch Office</option>
                                            <option value="Warehouse" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                            <option value="Other" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                        </optgroup>
                                        <!-- Service Location Options -->
                                        <optgroup id="service-location-group" label="Service Locations">
                                            <option value="Project Site" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                            <option value="Client Office" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Client Office' ? 'selected' : '' ?>>Client Office</option>
                                            <option value="Service Provider Office" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Service Provider Office' ? 'selected' : '' ?>>Service Provider Office</option>
                                            <option value="Digital/Email" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Digital/Email' ? 'selected' : '' ?>>Digital/Email</option>
                                            <option value="Multiple Locations" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'Multiple Locations' ? 'selected' : '' ?>>Multiple Locations</option>
                                            <option value="N/A" <?= ($formData['delivery_location'] ?? $procurementOrder['delivery_location'] ?? '') == 'N/A' ? 'selected' : '' ?>>N/A</option>
                                        </optgroup>
                                    </select>
                                    <small class="form-text text-muted" id="delivery-location-help">
                                        Specify where items should be delivered
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Order Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                               value="<?= htmlspecialchars($formData['title'] ?? $procurementOrder['title'] ?? '') ?>" 
                                   placeholder="Brief description of the procurement order" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="package_scope" class="form-label">Package Scope</label>
                            <textarea class="form-control" id="package_scope" name="package_scope" rows="3"
                                      placeholder="Detailed description of the procurement package..."><?= htmlspecialchars($formData['package_scope'] ?? $procurementOrder['package_scope'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                      placeholder="Additional notes or special instructions..."><?= htmlspecialchars($formData['notes'] ?? $procurementOrder['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Order Items
                        </h6>
                        <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                            <i class="bi bi-plus-circle me-1"></i>Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="itemsContainer">
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <div class="item-row border rounded p-3 mb-3" data-index="<?= $index ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="mb-0">Item #<?= $index + 1 ?></h6>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
                                                    <select class="form-select category-select" name="items[<?= $index ?>][category_id]" onchange="updateQuantityField(this)">
                                                        <option value="">Select Category</option>
                                                        <?php if (isset($categories)): ?>
                                                            <?php foreach ($categories as $category): ?>
                                                                <option value="<?= $category['id'] ?>" 
                                                                        data-consumable="<?= $category['is_consumable'] ?>"
                                                                        <?= ($item['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($category['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand</label>
                                                    <input type="text" class="form-control" name="items[<?= $index ?>][brand]" 
                                                           value="<?= htmlspecialchars($item['brand'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Model</label>
                                                    <input type="text" class="form-control" name="items[<?= $index ?>][model]" 
                                                           value="<?= htmlspecialchars($item['model'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Specifications</label>
                                            <textarea class="form-control" name="items[<?= $index ?>][specifications]" rows="2"><?= htmlspecialchars($item['specifications'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control quantity-input" name="items[<?= $index ?>][quantity]" 
                                                           value="<?= htmlspecialchars($item['quantity'] ?? '1') ?>" min="1" required>
                                                    <div class="form-text quantity-help">
                                                        <span class="quantity-consumable-text" style="display: none;">
                                                            <i class="bi bi-info-circle me-1"></i>Variable quantity for consumable items
                                                        </span>
                                                <span class="quantity-non-consumable-text">
                                                    <i class="bi bi-info-circle me-1"></i>Specify quantity to order (individual assets will be created)
                                                </span>
                                                    </div>
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
                                                    <input type="number" class="form-control unit-price-input" name="items[<?= $index ?>][unit_price]" 
                                                           value="<?= htmlspecialchars($item['unit_price'] ?? '0') ?>" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-label">Total</label>
                                                    <input type="text" class="form-control item-total" readonly 
                                                           value="₱<?= number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2) ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <input type="hidden" name="items[<?= $index ?>][id]" value="<?= $item['id'] ?? '' ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Default empty item -->
                                <div class="item-row border rounded p-3 mb-3" data-index="0">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="mb-0">Item #1</h6>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Item Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="items[0][item_name]" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="items[0][category_id]">
                                                    <option value="">Select Category</option>
                                                    <?php if (isset($categories)): ?>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?= $category['id'] ?>">
                                                                <?= htmlspecialchars($category['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Brand</label>
                                                <input type="text" class="form-control" name="items[0][brand]">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Model</label>
                                                <input type="text" class="form-control" name="items[0][model]">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Specifications</label>
                                        <textarea class="form-control" name="items[0][specifications]" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control quantity-input" name="items[0][quantity]" 
                                                       value="1" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Unit</label>
                                                <input type="text" class="form-control" name="items[0][unit]" value="pcs">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control unit-price-input" name="items[0][unit_price]" 
                                                       value="0" step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Total</label>
                                                <input type="text" class="form-control item-total" readonly value="₱0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> You can add multiple items to this procurement order. Each item will be tracked separately for asset generation.
                        </div>
                    </div>
                </div>

                <!-- File Attachments -->
                <?php
                // Get allowed file types based on PO type and status
                require_once APP_ROOT . '/models/ProcurementOrderModel.php';
                $isRetroactive = !empty($procurementOrder['is_retroactive']) && $procurementOrder['is_retroactive'] == 1;
                $currentStatus = $procurementOrder['status'] ?? 'Draft';
                $allowedFileTypes = ProcurementOrderModel::getAllowedFileTypes($isRetroactive, $currentStatus);
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-paperclip me-2"></i>File Attachments
                            <?php if ($isRetroactive): ?>
                                <span class="badge bg-warning text-dark ms-2">Retroactive PO</span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($isRetroactive): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-clock-history me-2"></i>
                                <strong>Retroactive Documentation:</strong> This PO was created for post-purchase documentation. Purchase receipts are required to validate the transaction.
                            </div>
                        <?php elseif (in_array($currentStatus, ['Draft', 'Pending', 'Reviewed', 'For Revision', 'Approved', 'Scheduled for Delivery', 'In Transit'])): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Current Status: <?= $currentStatus ?></strong> - Purchase receipts will be available after delivery completion.
                            </div>
                        <?php elseif (in_array($currentStatus, ['Delivered', 'Received'])): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Current Status: <?= $currentStatus ?></strong> - Purchase receipts and supporting documents can now be uploaded.
                            </div>
                        <?php endif; ?>
                        <?php foreach ($allowedFileTypes as $fileType => $config): ?>
                        <div class="mb-3">
                            <label for="<?= $fileType ?>" class="form-label">
                                <?= $config['label'] ?>
                                <?php if ($config['required']): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if (!$config['allowed']): ?>
                                <!-- Field not allowed for current status -->
                                <div class="alert alert-light border">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Not Available:</strong> <?= htmlspecialchars($config['help']) ?>
                                </div>
                            <?php else: ?>
                                <!-- Field is allowed -->
                                <?php if (!empty($procurementOrder[$fileType])): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Current file: <strong><?= htmlspecialchars($procurementOrder[$fileType]) ?></strong></small>
                                        <a href="?route=procurement-orders/file&id=<?= $procurementOrder['id'] ?>&type=<?= $fileType ?>&action=view" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <input type="file" 
                                       class="form-control" 
                                       id="<?= $fileType ?>" 
                                       name="<?= $fileType ?>" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       <?= $config['required'] ? 'required' : '' ?>>
                                <div class="form-text"><?= htmlspecialchars($config['help']) ?> (PDF, DOC, DOCX, JPG, PNG - Max 10MB)</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($procurementOrder['is_retroactive']): ?>
                        <div class="mb-3">
                            <label for="retroactive_reason" class="form-label">Retroactive Reason</label>
                            <textarea name="retroactive_reason" id="retroactive_reason" class="form-control" rows="2"
                                      placeholder="Explain why this PO is being created retroactively"><?= htmlspecialchars($formData['retroactive_reason'] ?? $procurementOrder['retroactive_reason'] ?? '') ?></textarea>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quotation Reference Fields -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="quotation_number" class="form-label">Quotation Number</label>
                                <input type="text" name="quotation_number" id="quotation_number" class="form-control" 
                                       placeholder="e.g. QUO-2024-001"
                                       value="<?= htmlspecialchars($formData['quotation_number'] ?? $procurementOrder['quotation_number'] ?? '') ?>">
                                <div class="form-text">Vendor's quotation reference number</div>
                            </div>
                            <div class="col-md-6">
                                <label for="quotation_date" class="form-label">Quotation Date</label>
                                <input type="date" name="quotation_date" id="quotation_date" class="form-control"
                                       value="<?= htmlspecialchars($formData['quotation_date'] ?? $procurementOrder['quotation_date'] ?? '') ?>">
                                <div class="form-text">Date of vendor quotation</div>
                            </div>
                        </div>
                        
                        <div class="mb-0">
                            <label for="file_upload_notes" class="form-label">File Upload Notes</label>
                            <textarea name="file_upload_notes" id="file_upload_notes" class="form-control" rows="2"
                                      placeholder="Add notes about the uploaded documents"><?= htmlspecialchars($formData['file_upload_notes'] ?? $procurementOrder['file_upload_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-calculator me-2"></i>Order Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="vat_rate" class="form-label">VAT Rate (%)</label>
                                <input type="number" name="vat_rate" id="vat_rate" class="form-control" 
                                       step="0.01" min="0" max="100" value="<?= htmlspecialchars($formData['vat_rate'] ?? $procurementOrder['vat_rate'] ?? '12.00') ?>"
                                       onchange="calculateTotals()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ewt_rate" class="form-label">EWT Rate (%)</label>
                                <input type="number" name="ewt_rate" id="ewt_rate" class="form-control" 
                                       step="0.01" min="0" max="100" value="<?= htmlspecialchars($formData['ewt_rate'] ?? $procurementOrder['ewt_rate'] ?? '2.00') ?>"
                                       onchange="calculateTotals()">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="handling_fee" class="form-label">Handling Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="handling_fee" id="handling_fee" class="form-control" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['handling_fee'] ?? $procurementOrder['handling_fee'] ?? '0') ?>"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_amount" class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="discount_amount" id="discount_amount" class="form-control" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['discount_amount'] ?? $procurementOrder['discount_amount'] ?? '0') ?>"
                                           onchange="calculateTotals()">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-3"><i class="bi bi-calculator me-2"></i>Order Summary</h6>
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
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Net Total:</span>
                                <span id="summary-total">₱0.00</span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="subtotal" id="subtotal" value="0">
                        <input type="hidden" name="vat_amount" id="vat_amount" value="0">
                        <input type="hidden" name="ewt_amount" id="ewt_amount" value="0">
                        <input type="hidden" name="net_total" id="net_total" value="0">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="save_draft" class="btn btn-outline-primary">
                                <i class="bi bi-save me-1"></i>Save as Draft
                            </button>
                            <?php if ($procurementOrder['is_retroactive']): ?>
                            <button type="submit" name="action" value="submit_retrospective" class="btn btn-warning">
                                <i class="bi bi-clock-history me-1"></i>Submit Retrospective for Approval
                            </button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>Update & Submit for Approval
                            </button>
                            <a href="?route=procurement-orders/view&id=<?= $procurementOrder['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php else: ?>
<div class="alert alert-danger mt-4">You do not have permission to edit this procurement order.</div>
<?php endif; ?>

<script>
let itemIndex = <?= count($items ?? []) ?: 1 ?>;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemHtml = `
        <div class="item-row border rounded p-3 mb-3" data-index="${itemIndex}">
            <div class="d-flex justify-content-between align-items-start mb-3">
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
                        <select class="form-select category-select" name="items[${itemIndex}][category_id]" 
                                onchange="updateQuantityField(this); updateDeliveryOptions()">
                            <option value="">Select Category</option>
                            <?php if (isset($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                            data-consumable="<?= $category['is_consumable'] ?>"
                                            data-generates-assets="<?= ($category['generates_assets'] ?? 1) ? 'true' : 'false' ?>"
                                            data-asset-type="<?= htmlspecialchars($category['asset_type'] ?? 'capital') ?>"
                                            data-expense-category="<?= htmlspecialchars($category['expense_category'] ?? '') ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Brand</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][brand]">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][model]">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Specifications</label>
                <textarea class="form-control" name="items[${itemIndex}][specifications]" rows="2"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input" name="items[${itemIndex}][quantity]" 
                               value="1" min="1" required>
                        <div class="form-text quantity-help">
                            <span class="quantity-consumable-text" style="display: none;">
                                <i class="bi bi-info-circle me-1"></i>Variable quantity for consumable items
                            </span>
                        <span class="quantity-non-consumable-text">
                            <i class="bi bi-info-circle me-1"></i>Specify quantity to order (individual assets will be created)
                        </span>
                        </div>
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
                        <input type="number" class="form-control unit-price-input" name="items[${itemIndex}][unit_price]" 
                               value="0" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Total</label>
                        <input type="text" class="form-control item-total" readonly value="₱0.00">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
    itemIndex++;
    
    // Attach event listeners to new inputs
    attachCalculationListeners();
    updateItemNumbers();
    calculateTotals();
}

function removeItem(button) {
    const itemRow = button.closest('.item-row');
    if (document.querySelectorAll('.item-row').length > 1) {
        itemRow.remove();
        updateItemNumbers();
        calculateTotals();
    } else {
        alert('At least one item is required.');
    }
}

function updateItemNumbers() {
    const itemRows = document.querySelectorAll('.item-row');
    itemRows.forEach((row, index) => {
        const header = row.querySelector('h6');
        header.textContent = `Item #${index + 1}`;
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
    calculateItemTotal({ target: quantityInput });
}

function attachCalculationListeners() {
    // Attach listeners to quantity and unit price inputs
    document.querySelectorAll('.quantity-input, .unit-price-input').forEach(input => {
        input.removeEventListener('input', calculateItemTotal);
        input.addEventListener('input', calculateItemTotal);
    });
    
    // Attach listeners to financial inputs
    ['vat_rate', 'ewt_rate', 'handling_fee', 'discount_amount'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateTotals);
    });
}

function calculateItemTotal(event) {
    const itemRow = event.target.closest('.item-row');
    const quantity = parseFloat(itemRow.querySelector('.quantity-input').value) || 0;
    const unitPrice = parseFloat(itemRow.querySelector('.unit-price-input').value) || 0;
    const total = quantity * unitPrice;
    
    itemRow.querySelector('.item-total').value = '₱' + total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    
    // Calculate subtotal from all items
    document.querySelectorAll('.item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price-input').value) || 0;
        subtotal += quantity * unitPrice;
    });
    
    // Get financial values
    const vatRate = parseFloat(document.getElementById('vat_rate').value) || 0;
    const ewtRate = parseFloat(document.getElementById('ewt_rate').value) || 0;
    const handlingFee = parseFloat(document.getElementById('handling_fee').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    // Calculate amounts
    const vatAmount = subtotal * (vatRate / 100);
    const ewtAmount = subtotal * (ewtRate / 100);
    const netTotal = subtotal + vatAmount - ewtAmount + handlingFee - discountAmount;
    
    // Update summary displays
    document.getElementById('summary-subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('summary-vat-rate').textContent = vatRate.toFixed(2);
    document.getElementById('summary-vat').textContent = '₱' + vatAmount.toFixed(2);
    document.getElementById('summary-ewt-rate').textContent = ewtRate.toFixed(2);
    document.getElementById('summary-ewt').textContent = '-₱' + ewtAmount.toFixed(2);
    document.getElementById('summary-handling').textContent = '₱' + handlingFee.toFixed(2);
    document.getElementById('summary-discount').textContent = '-₱' + discountAmount.toFixed(2);
    document.getElementById('summary-total').textContent = '₱' + netTotal.toFixed(2);
    
    // Update hidden inputs
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('vat_amount').value = vatAmount.toFixed(2);
    document.getElementById('ewt_amount').value = ewtAmount.toFixed(2);
    document.getElementById('net_total').value = netTotal.toFixed(2);
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    attachCalculationListeners();
    calculateTotals();
    
    // Initialize quantity fields for existing items
    document.querySelectorAll('.category-select').forEach(select => {
        updateQuantityField(select);
    });
});

// Form validation
document.getElementById('procurementOrderForm').addEventListener('submit', function(e) {
    const itemRows = document.querySelectorAll('.item-row');
    if (itemRows.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the procurement order.');
        return false;
    }
    
    // Validate that all required fields are filled
    let hasErrors = false;
    itemRows.forEach(row => {
        const itemName = row.querySelector('input[name*="[item_name]"]').value.trim();
        const quantity = row.querySelector('input[name*="[quantity]"]').value;
        const unitPrice = row.querySelector('input[name*="[unit_price]"]').value;
        
        if (!itemName || !quantity || !unitPrice) {
            hasErrors = true;
        }
    });
    
    if (hasErrors) {
        e.preventDefault();
        alert('Please fill in all required fields for each item.');
        return false;
    }
});

// Update delivery options based on selected categories (same logic as create form)
function updateDeliveryOptions() {
    const categorySelects = document.querySelectorAll('.category-select');
    const physicalGroup = document.getElementById('physical-delivery-group');
    const serviceGroup = document.getElementById('service-delivery-group');
    const physicalLocationGroup = document.getElementById('physical-location-group');
    const serviceLocationGroup = document.getElementById('service-location-group');
    
    let hasPhysicalItems = false;
    let hasServiceItems = false;
    
    // Analyze selected categories
    categorySelects.forEach(select => {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const generatesAssets = selectedOption.getAttribute('data-generates-assets') === 'true' || 
                                   selectedOption.getAttribute('data-generates-assets') === null; // Default to true for backward compatibility
            
            if (generatesAssets) {
                hasPhysicalItems = true;
            } else {
                hasServiceItems = true;
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
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update delivery options based on existing categories
    updateDeliveryOptions();
    
    // Add event listeners to existing category selects
    document.querySelectorAll('.category-select').forEach(select => {
        select.addEventListener('change', updateDeliveryOptions);
    });
});

</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Procurement Order - ConstructLink™';
$pageHeader = 'Edit Procurement Order #' . htmlspecialchars($procurementOrder['po_number'] ?? 'DRAFT-' . $procurementOrder['id']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Procurement Orders', 'url' => '?route=procurement-orders'],
    ['title' => 'Edit Order', 'url' => '?route=procurement-orders/edit&id=' . $procurementOrder['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
