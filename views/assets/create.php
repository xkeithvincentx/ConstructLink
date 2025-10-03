<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="?route=assets" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <span class="d-none d-sm-inline">Back to Assets</span>
        <span class="d-sm-none">Back</span>
    </a>
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
        <strong>Please correct the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!in_array($user['role_name'], $roleConfig['assets/create'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to create a new asset.</div>
<?php else: ?>
<div class="row">
    <div class="col-lg-8 col-xl-9">
        <!-- Asset Creation Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <!-- Quick Entry Section (will be added dynamically) -->
                <div id="quick-entry-container"></div>
                
                <form method="POST" action="?route=assets/create" id="assetForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ref" class="form-label">Asset Reference</label>
                                <input type="text" class="form-control" id="ref" name="ref" 
                                       value="<?= htmlspecialchars($formData['ref'] ?? '') ?>"
                                       placeholder="Leave blank to auto-generate">
                                <div class="form-text">Leave blank to auto-generate with system prefix</div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Asset Name <span class="text-danger">*</span>
                                    <small class="text-muted ms-2">Auto-generated from equipment selection</small>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>" 
                                           placeholder="Select equipment type and subtype to auto-generate name..."
                                           readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="edit-name-btn" title="Edit name manually">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                                
                                <!-- Name Preview Alert -->
                                <div class="alert alert-success mt-2 border-0 bg-light" id="name-preview" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-magic text-success me-2"></i>
                                        <div class="flex-grow-1">
                                            <strong>Generated Name Preview:</strong>
                                            <div id="preview-name" class="text-success mt-1 fw-bold"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-success" id="apply-generated-name">
                                            Use This Name
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter the asset name.
                                </div>
                                <!-- Hidden fields for intelligent naming -->
                                <input type="hidden" id="generated_name" name="generated_name">
                                <input type="hidden" id="name_components" name="name_components">
                                <input type="hidden" id="is_manual_name" name="is_manual_name" value="0">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Detailed description of the asset..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Classification -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-tags me-1"></i>Classification & Details
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    Category <span class="text-danger">*</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-category-btn" title="Clear category to see all equipment types">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </button>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required data-disciplines="true">
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    data-disciplines="<?= htmlspecialchars($category['discipline_tags'] ?? '') ?>"
                                                    data-keywords="<?= htmlspecialchars($category['search_keywords'] ?? '') ?>"
                                                    <?= ($formData['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                                <?php if (!empty($category['parent_name'])): ?>
                                                    (<?= htmlspecialchars($category['parent_name']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a category.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
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
                                <div class="invalid-feedback">
                                    Please select a project.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Intelligent Equipment Classification Section -->
                    <div class="row mb-4" id="equipment-classification-section" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-success border-bottom pb-2 mb-3">
                                <i class="bi bi-cpu me-1"></i>Intelligent Equipment Classification
                                <small class="text-muted ms-2">Smart equipment type selection with auto-naming</small>
                            </h6>
                        </div>
                        
                        <!-- Equipment Type Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="equipment_type_id" class="form-label">
                                    Equipment Type <span class="text-danger">*</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-equipment-btn" title="Clear equipment selection and reset to all types">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                    <i class="bi bi-info-circle ms-1" title="Main equipment category (e.g., Drill, Grinder, Welder)"></i>
                                </label>
                                <select class="form-select" id="equipment_type_id" name="equipment_type_id" required>
                                    <option value="">Select Equipment Type</option>
                                </select>
                                <div class="form-text">What type of equipment is this?</div>
                            </div>
                        </div>
                        
                        <!-- Subtype Selection -->  
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subtype_id" class="form-label">
                                    Specific Subtype <span class="text-danger" id="subtype-required-asterisk" style="display: none;">*</span>
                                    <i class="bi bi-info-circle ms-1" title="Specific variation (e.g., Electric, Cordless, Angle)"></i>
                                </label>
                                <select class="form-select" id="subtype_id" name="subtype_id">
                                    <option value="">Select Specific Subtype</option>
                                </select>
                                <div class="form-text">What specific type/variation is it? (Required only if subtypes are available)</div>
                            </div>
                        </div>
                        
                        <!-- Equipment Details Display -->
                        <div class="col-12" id="equipment-details" style="display: none;">
                            <div class="alert alert-info border-0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><i class="bi bi-gear me-1"></i>Material Type:</strong> 
                                        <span id="material-type-display">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="bi bi-lightning me-1"></i>Power Source:</strong> 
                                        <span id="power-source-display">-</span>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <strong><i class="bi bi-wrench me-1"></i>Application:</strong> 
                                        <span id="application-display">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brand & Discipline Smart Section -->
                    <div class="row mb-4" id="brand-discipline-section">
                        <div class="col-12">
                            <h6 class="text-info border-bottom pb-2 mb-3">
                                <i class="bi bi-award me-1"></i>Brand & Engineering Usage
                                <small class="text-muted ms-2">Smart classification based on category selection</small>
                            </h6>
                        </div>
                        
                        <!-- Brand Section -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand/Manufacturer</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">Select Brand</option>
                                    <?php if (!empty($brands)): ?>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand['official_name']) ?>" 
                                                    data-brand-id="<?= $brand['id'] ?>"
                                                    data-quality="<?= $brand['quality_tier'] ?>"
                                                    <?= ($formData['brand'] ?? '') == $brand['official_name'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand['official_name']) ?>
                                                <?php if (!empty($brand['quality_tier'])): ?>
                                                    - <?= ucfirst($brand['quality_tier']) ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Select from verified construction brands</div>
                                <!-- Hidden fields for brand standardization -->
                                <input type="hidden" id="standardized_brand" name="standardized_brand">
                                <input type="hidden" id="brand_id" name="brand_id">
                            </div>
                        </div>
                        
                        <!-- Model/Serial Section -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label for="model" class="form-label">Model</label>
                                        <input type="text" class="form-control" id="model" name="model" 
                                               value="<?= htmlspecialchars($formData['model'] ?? '') ?>" 
                                               placeholder="e.g., XR20">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <label for="serial_number" class="form-label">Serial #</label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                               value="<?= htmlspecialchars($formData['serial_number'] ?? '') ?>" 
                                               placeholder="Optional">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Disciplines Section (Initially Hidden, Shows Based on Category) -->
                        <div id="discipline-section" class="row" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-3">
                                    <i class="bi bi-diagram-3 me-1"></i>
                                    <strong>Multi-Disciplinary Classification:</strong> This asset type is used across multiple engineering disciplines. Select the applicable ones.
                                </div>
                            </div>
                            
                            <div class="col-lg-4 col-md-12">
                                <div class="mb-3">
                                    <label for="primary_discipline" class="form-label">Primary Discipline <span class="text-danger">*</span></label>
                                    <select class="form-select" id="primary_discipline" name="primary_discipline">
                                        <option value="">Select Primary Use</option>
                                    </select>
                                    <div class="form-text">Main discipline where this asset will be used</div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Also Used In:</label>
                                    <div id="discipline-checkboxes" class="border rounded p-3 bg-light" style="max-height: 160px; overflow-y: auto;">
                                        <!-- Dynamically populated discipline checkboxes -->
                                    </div>
                                    <div class="form-text">Select all applicable disciplines for this asset</div>
                                </div>
                            </div>
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
                            <div class="mb-3">
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
                        </div>
                        
                        <div class="col-md-6" id="procurement_item_container" style="display: none;">
                            <div class="mb-3">
                                <label for="procurement_item_id" class="form-label">Procurement Item <span class="text-danger">*</span></label>
                                <select class="form-select" id="procurement_item_id" name="procurement_item_id">
                                    <option value="">Select Item</option>
                                </select>
                                <div class="form-text">Select specific item from procurement order</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
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
                        </div>
                        
                        
                        <div class="col-md-6">
                            <div class="mb-3">
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
                        </div>
                        
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-3">
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
                        
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       min="1" value="<?= htmlspecialchars($formData['quantity'] ?? '1') ?>">
                                <div class="form-text" id="quantity-help">
                                    <span id="quantity-consumable-text" style="display: none;">
                                        <i class="bi bi-info-circle me-1"></i>Quantity for consumable items
                                    </span>
                                    <span id="quantity-non-consumable-text">
                                        <i class="bi bi-info-circle me-1"></i>Fixed at 1 for non-consumable items
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <select class="form-select" id="unit" name="unit">
                                    <option value="pcs" <?= ($formData['unit'] ?? 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces</option>
                                    <option value="unit" <?= ($formData['unit'] ?? '') === 'unit' ? 'selected' : '' ?>>Unit</option>
                                    <option value="set" <?= ($formData['unit'] ?? '') === 'set' ? 'selected' : '' ?>>Set</option>
                                    <option value="box" <?= ($formData['unit'] ?? '') === 'box' ? 'selected' : '' ?>>Box</option>
                                    <option value="kg" <?= ($formData['unit'] ?? '') === 'kg' ? 'selected' : '' ?>>Kilogram</option>
                                    <option value="m" <?= ($formData['unit'] ?? '') === 'm' ? 'selected' : '' ?>>Meter</option>
                                    <option value="m3" <?= ($formData['unit'] ?? '') === 'm3' ? 'selected' : '' ?>>Cubic Meter</option>
                                    <option value="sqm" <?= ($formData['unit'] ?? '') === 'sqm' ? 'selected' : '' ?>>Square Meter</option>
                                    <option value="l" <?= ($formData['unit'] ?? '') === 'l' ? 'selected' : '' ?>>Liter</option>
                                    <option value="lot" <?= ($formData['unit'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
                                </select>
                                <div class="form-text">Auto-selected based on equipment type</div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="specifications" class="form-label">Detailed Specifications</label>
                                <textarea class="form-control" id="specifications" name="specifications" rows="3"
                                          placeholder="Technical specifications, dimensions, capacity, etc..."><?= htmlspecialchars($formData['specifications'] ?? '') ?></textarea>
                            </div>
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
                            <div class="mb-3">
                                <label for="acquired_date" class="form-label">Acquired Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="acquired_date" name="acquired_date" 
                                       value="<?= htmlspecialchars($formData['acquired_date'] ?? '') ?>" required>
                                <div class="invalid-feedback">
                                    Please enter the acquisition date.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="acquisition_cost" class="form-label">Acquisition Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="acquisition_cost" name="acquisition_cost" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['acquisition_cost'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['unit_cost'] ?? '') ?>">
                                </div>
                                <div class="form-text">Individual unit cost if different from total</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                       value="<?= htmlspecialchars($formData['warranty_expiry'] ?? '') ?>">
                            </div>
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
                            <div class="mb-3">
                                <label for="location" class="form-label">Current Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?= htmlspecialchars($formData['location'] ?? '') ?>"
                                       placeholder="Warehouse, Site office, etc.">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="condition_notes" class="form-label">Condition Notes</label>
                                <textarea class="form-control" id="condition_notes" name="condition_notes" rows="2"
                                          placeholder="Current condition, any defects, etc..."><?= htmlspecialchars($formData['condition_notes'] ?? '') ?></textarea>
                            </div>
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
    
    <div class="col-lg-4 col-xl-3">
        <!-- Asset Creation Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Asset Creation Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Asset reference will be auto-generated if left blank
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Link to procurement order for purchased items
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Mark as client-supplied if provided by client
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Include detailed specifications for technical assets
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        QR code will be automatically generated
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        New assets are set to "Available" status
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Required Fields -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-exclamation-circle me-2"></i>Required Fields
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-1">• Asset Name</li>
                    <li class="mb-1">• Category</li>
                    <li class="mb-1">• Project Assignment</li>
                    <li class="mb-0">• Acquired Date</li>
                </ul>
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
// Get CSRF token value for API calls
const CSRFTokenValue = document.querySelector('input[name="csrf_token"]')?.value || '';

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('assetForm');
    const procurementOrderSelect = document.getElementById('procurement_order_id');
    const procurementItemContainer = document.getElementById('procurement_item_container');
    const procurementItemSelect = document.getElementById('procurement_item_id');
    const vendorSelect = document.getElementById('vendor_id');
    const acquisitionCostInput = document.getElementById('acquisition_cost');
    const unitCostInput = document.getElementById('unit_cost');
    const categorySelect = document.getElementById('category_id');
    const quantityInput = document.getElementById('quantity');
    const quantityConsumableText = document.getElementById('quantity-consumable-text');
    const quantityNonConsumableText = document.getElementById('quantity-non-consumable-text');
    
    // Handle category selection for quantity behavior
    categorySelect.addEventListener('change', function() {
        updateQuantityField();
    });
    
    // Function to update quantity field based on category
    function updateQuantityField() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        
        // Check if category data is available (we'll need to fetch this via AJAX)
        if (categorySelect.value) {
            fetch(`?route=api/categories/details&id=${categorySelect.value}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.category) {
                        const isConsumable = data.category.is_consumable == 1;
                        
                        if (isConsumable) {
                            // Enable quantity input for consumables
                            quantityInput.disabled = false;
                            quantityInput.min = 1;
                            quantityConsumableText.style.display = 'inline';
                            quantityNonConsumableText.style.display = 'none';
                        } else {
                            // Disable quantity input for non-consumables, set to 1
                            quantityInput.value = 1;
                            quantityInput.disabled = true;
                            quantityConsumableText.style.display = 'none';
                            quantityNonConsumableText.style.display = 'inline';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching category details:', error);
                    // Default to non-consumable behavior
                    quantityInput.value = 1;
                    quantityInput.disabled = true;
                    quantityConsumableText.style.display = 'none';
                    quantityNonConsumableText.style.display = 'inline';
                });
        } else {
            // No category selected, default behavior
            quantityInput.disabled = false;
            quantityConsumableText.style.display = 'none';
            quantityNonConsumableText.style.display = 'inline';
        }
    }

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
        // Debug: Check name field value before submission
        const nameField = document.getElementById('name');
        console.log('Form submission - name field value:', nameField.value);
        console.log('Form submission - name field readOnly:', nameField.readOnly);
        
        // Fallback: If name field is empty but we have a generated name, use it
        if (!nameField.value.trim() && currentGeneratedName) {
            console.log('Using generated name as fallback:', currentGeneratedName);
            nameField.value = currentGeneratedName;
        }
        
        // Last resort fallback: Generate simple name from available data
        if (!nameField.value.trim()) {
            const equipmentTypeSelect = document.getElementById('equipment_type_id');
            const categorySelect = document.getElementById('category_id');
            
            if (equipmentTypeSelect && equipmentTypeSelect.value) {
                const equipmentText = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].textContent;
                const fallbackName = equipmentText + ' - Asset';
                console.log('Last resort fallback name:', fallbackName);
                nameField.value = fallbackName;
            } else if (categorySelect && categorySelect.value) {
                const categoryText = categorySelect.options[categorySelect.selectedIndex].textContent;
                const fallbackName = categoryText + ' - Asset';
                console.log('Category-based fallback name:', fallbackName);
                nameField.value = fallbackName;
            }
        }
        
        // Additional check: If equipment is selected but no name, warn user
        const equipmentTypeSelect = document.getElementById('equipment_type_id');
        const subtypeSelect = document.getElementById('subtype_id');
        if (equipmentTypeSelect && subtypeSelect && equipmentTypeSelect.value && subtypeSelect.value && !nameField.value.trim()) {
            console.warn('Create form: Equipment selected but name still empty - may need to wait for name generation');
        }
        
        const requiredFields = ['category_id', 'project_id', 'acquired_date'];
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
    
    // Initialize quantity field based on current category selection
    updateQuantityField();
    
    // Debug: Check discipline section initialization
    const disciplineSectionDebug = document.getElementById('discipline-section');
    if (disciplineSectionDebug) {
        console.log('Discipline section ready for population');
    }
    
    // =====================================================
    // Intelligent Equipment Classification & Auto-Naming System
    // =====================================================
    
    const equipmentClassificationSection = document.getElementById('equipment-classification-section');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    const equipmentDetails = document.getElementById('equipment-details');
    const nameInput = document.getElementById('name');
    const namePreview = document.getElementById('name-preview');
    const previewNameDiv = document.getElementById('preview-name');
    const editNameBtn = document.getElementById('edit-name-btn');
    const applyNameBtn = document.getElementById('apply-generated-name');
    
    let currentGeneratedName = '';
    let currentNameComponents = {};
    
    // Initialize all equipment types storage
    let allEquipmentTypes = [];
    let equipmentTypeToCategory = {}; // Map equipment type ID to category data
    
    // Load all equipment types on page load
    loadAllEquipmentTypes();
    
    function loadAllEquipmentTypes() {
        fetch(`?route=api/intelligent-naming&action=all-equipment-types`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    allEquipmentTypes = data.data;
                    console.log('Loaded equipment types:', allEquipmentTypes.length);
                    
                    // Build equipment type to category mapping
                    data.data.forEach(type => {
                        equipmentTypeToCategory[type.id] = {
                            categoryId: type.category_id,
                            categoryName: type.category_name
                        };
                    });
                    console.log('Built equipment type to category mapping:', equipmentTypeToCategory);
                }
            })
            .catch(error => {
                console.error('Error loading all equipment types:', error);
            });
    }
    
    // Show/hide equipment classification section based on category selection
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        // Check if we need to preserve equipment selection due to auto-category-selection
        const preserveSelection = window.preserveEquipmentSelection;
        
        if (categoryId) {
            equipmentClassificationSection.style.display = 'block';
            filterEquipmentTypesByCategory(categoryId);
            
            // Restore equipment selection if it was auto-triggered
            if (preserveSelection) {
                console.log('Restoring equipment selection in create.php:', preserveSelection);
                setTimeout(() => {
                    equipmentTypeSelect.value = preserveSelection;
                    
                    // Also restore Select2 selection if exists
                    if (window.jQuery && window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                        window.jQuery('#equipment_type_id').val(preserveSelection).trigger('change');
                    }
                    
                    // Clear the preserve flag
                    window.preserveEquipmentSelection = null;
                }, 100);
            }
        } else {
            equipmentClassificationSection.style.display = 'none';
            clearEquipmentClassification();
        }
    });
    
    // Filter equipment types by category
    function filterEquipmentTypesByCategory(categoryId) {
        if (!categoryId) {
            clearEquipmentTypes();
            return;
        }
        
        // Filter equipment types for the selected category
        const filteredTypes = allEquipmentTypes.filter(type => type.category_id == categoryId);
        console.log('Filtering by category', categoryId, '- found', filteredTypes.length, 'types');
        populateEquipmentTypes(filteredTypes);
    }
    
    function populateEquipmentTypes(equipmentTypes) {
        equipmentTypeSelect.innerHTML = '<option value="">Select Equipment Type</option>';
        equipmentTypes.forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            option.setAttribute('data-description', type.description || '');
            // Add category data attributes for auto-selection functionality
            option.dataset.categoryId = type.category_id;
            option.dataset.categoryName = type.category_name;
            equipmentTypeSelect.appendChild(option);
        });
        
        clearSubtypes();
        hideEquipmentDetails();
        hideNamePreview();
    }
    
    // Load subtypes when equipment type changes and auto-select category
    equipmentTypeSelect.addEventListener('change', function() {
        const equipmentTypeId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        
        console.log('Equipment type changed:', equipmentTypeId);
        console.log('Selected option:', selectedOption);
        console.log('Dataset categoryId:', selectedOption?.dataset?.categoryId);
        console.log('Dataset categoryName:', selectedOption?.dataset?.categoryName);
        
        // Use API-based category auto-selection
        if (equipmentTypeId && !window.preventCategoryAutoSelection) {
            // Use the new API to get category info and auto-populate
            fetch(`?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Create form: API response:', data);
                    if (data.success && data.data && data.data.category_id) {
                        const currentCategoryId = categorySelect.value;
                        const targetCategoryId = data.data.category_id;
                        
                        console.log('Create form: Current category:', currentCategoryId, 'Target:', targetCategoryId);
                        
                        if (!currentCategoryId || currentCategoryId != targetCategoryId) {
                            console.log('Create form: Auto-selecting category:', targetCategoryId, data.data.category_name);
                            
                            // Set category value
                            categorySelect.value = targetCategoryId;
                            
                            // Trigger Select2 update if applicable
                            if (window.jQuery && window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                                window.jQuery('#category_id').val(targetCategoryId).trigger('change');
                            }
                            
                            // Show feedback
                            showAutoSelectionMessage('Category automatically selected: ' + data.data.category_name);
                        }
                    }
                })
                .catch(error => {
                    console.error('Create form: Error loading equipment type details:', error);
                });
        }
        
        if (this.value) {
            loadSubtypes(this.value);
            updateIntelligentUnit(this.value);
        } else {
            clearSubtypes();
            hideEquipmentDetails();
            hideNamePreview();
        }
    });
    
    function loadSubtypes(equipmentTypeId) {
        fetch(`?route=api/intelligent-naming&action=subtypes&equipment_type_id=${equipmentTypeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateSubtypes(data.data);
                } else {
                    console.error('Failed to load subtypes:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading subtypes:', error);
            });
    }
    
    function populateSubtypes(subtypes) {
        subtypeSelect.innerHTML = '<option value="">Select Specific Subtype</option>';
        
        // Handle dynamic requirement based on subtype availability
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        
        if (subtypes.length === 0) {
            // No subtypes available - make field optional
            subtypeSelect.innerHTML = '<option value="">No subtypes available</option>';
            subtypeSelect.removeAttribute('required');
            if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
            console.log('🔧 No subtypes found - field is now optional');
            hideEquipmentDetails();
            hideNamePreview();
            return;
        } else {
            // Subtypes available - make field required
            subtypeSelect.setAttribute('required', 'required');
            if (subtypeAsterisk) subtypeAsterisk.style.display = 'inline';
            console.log('🔧 Subtypes found - field is now required');
        }
        
        subtypes.forEach(subtype => {
            const option = document.createElement('option');
            option.value = subtype.id;
            option.textContent = subtype.subtype_name;
            option.setAttribute('data-material', subtype.material_type || '');
            option.setAttribute('data-power', subtype.power_source || '');
            option.setAttribute('data-application', subtype.application_area || '');
            subtypeSelect.appendChild(option);
        });
        
        hideEquipmentDetails();
        hideNamePreview();
    }
    
    // Generate name preview when subtype changes
    subtypeSelect.addEventListener('change', function() {
        if (this.value && equipmentTypeSelect.value) {
            const selectedOption = this.options[this.selectedIndex];
            showEquipmentDetails(selectedOption);
            generateNamePreview();
        } else {
            hideEquipmentDetails();
            hideNamePreview();
        }
    });
    
    function showEquipmentDetails(subtypeOption) {
        const materialType = subtypeOption.getAttribute('data-material') || 'N/A';
        const powerSource = subtypeOption.getAttribute('data-power') || 'N/A';
        const application = subtypeOption.getAttribute('data-application') || 'N/A';
        
        document.getElementById('material-type-display').textContent = materialType;
        document.getElementById('power-source-display').textContent = powerSource;
        document.getElementById('application-display').textContent = application;
        
        equipmentDetails.style.display = 'block';
    }
    
    function generateNamePreview() {
        const equipmentTypeId = equipmentTypeSelect.value;
        const subtypeId = subtypeSelect.value;
        const brand = document.getElementById('brand')?.value || '';
        const model = document.getElementById('model')?.value || '';
        
        if (!equipmentTypeId || !subtypeId) {
            hideNamePreview();
            return;
        }
        
        const params = new URLSearchParams({
            action: 'generate-name',
            equipment_type_id: equipmentTypeId,
            subtype_id: subtypeId,
            brand: brand,
            model: model
        });
        
        fetch(`?route=api/intelligent-naming&${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentGeneratedName = data.data.generated_name;
                    currentNameComponents = data.data.name_components;
                    
                    previewNameDiv.textContent = currentGeneratedName;
                    namePreview.style.display = 'block';
                    
                    // Auto-populate name field (like legacy form behavior)
                    if (nameInput.readOnly) {
                        nameInput.value = currentGeneratedName;
                        console.log('Auto-populated name field:', currentGeneratedName);
                    }
                } else {
                    console.error('Failed to generate name:', data.message);
                    hideNamePreview();
                }
            })
            .catch(error => {
                console.error('Error generating name:', error);
                hideNamePreview();
            });
    }
    
    // Apply generated name
    applyNameBtn.addEventListener('click', function() {
        nameInput.value = currentGeneratedName;
        document.getElementById('generated_name').value = currentGeneratedName;
        document.getElementById('name_components').value = JSON.stringify(currentNameComponents);
        document.getElementById('is_manual_name').value = '0';
        
        hideNamePreview();
        nameInput.classList.add('is-valid');
        
        // Show success feedback
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
        successDiv.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>
            <strong>Name Applied:</strong> ${currentGeneratedName}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        nameInput.parentNode.insertAdjacentElement('afterend', successDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 5000);
    });
    
    // Manual name editing
    editNameBtn.addEventListener('click', function() {
        nameInput.readOnly = false;
        nameInput.placeholder = 'Enter asset name manually...';
        nameInput.focus();
        nameInput.select();
        
        document.getElementById('is_manual_name').value = '1';
        
        editNameBtn.innerHTML = '<i class="bi bi-check"></i>';
        editNameBtn.title = 'Confirm manual edit';
        editNameBtn.onclick = function() {
            nameInput.readOnly = true;
            nameInput.placeholder = 'Select equipment type and subtype to auto-generate name...';
            editNameBtn.innerHTML = '<i class="bi bi-pencil"></i>';
            editNameBtn.title = 'Edit name manually';
            editNameBtn.onclick = arguments.callee.caller;
        };
    });
    
    // Update name preview when brand/model changes
    const brandInput = document.getElementById('brand');
    const modelInput = document.getElementById('model');
    
    if (brandInput) {
        brandInput.addEventListener('change', debounce(generateNamePreview, 300));
    }
    if (modelInput) {
        modelInput.addEventListener('change', debounce(generateNamePreview, 300));
    }
    
    // Utility functions
    function clearEquipmentClassification() {
        clearEquipmentTypes();
        clearSubtypes();
        hideEquipmentDetails();
        hideNamePreview();
        nameInput.value = '';
        nameInput.readOnly = true;
    }
    
    function clearEquipmentTypes() {
        equipmentTypeSelect.innerHTML = '<option value="">Select Equipment Type</option>';
    }
    
    function clearSubtypes() {
        subtypeSelect.innerHTML = '<option value="">Select Specific Subtype</option>';
        // Clear subtype requirement when cleared
        subtypeSelect.removeAttribute('required');
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
    }
    
    function hideEquipmentDetails() {
        equipmentDetails.style.display = 'none';
    }
    
    function hideNamePreview() {
        namePreview.style.display = 'none';
        currentGeneratedName = '';
        currentNameComponents = {};
        
        // Clear name field if it was auto-populated
        if (nameInput.readOnly && nameInput.value) {
            nameInput.value = '';
            console.log('Cleared auto-populated name field');
        }
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize if category is pre-selected
    if (categorySelect.value) {
        equipmentClassificationSection.style.display = 'block';
        filterEquipmentTypesByCategory(categorySelect.value);
    }
    
    // Intelligent Unit Auto-Population
    window.updateIntelligentUnit = function(equipmentTypeId, subtypeId = null) {
        console.log('Updating intelligent unit for equipment type:', equipmentTypeId, 'subtype:', subtypeId);
        
        if (!equipmentTypeId) return;
        
        const unitSelect = document.getElementById('unit');
        if (!unitSelect) return;
        
        const params = new URLSearchParams({
            action: 'intelligent-unit',
            equipment_type_id: equipmentTypeId
        });
        
        if (subtypeId) {
            params.append('subtype_id', subtypeId);
        }
        
        fetch(`?route=api/intelligent-naming&${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.unit) {
                    const suggestedUnit = data.data.unit;
                    console.log('Intelligent unit suggestion:', suggestedUnit);
                    
                    // Check if the suggested unit exists in the dropdown
                    const optionExists = Array.from(unitSelect.options).some(option => option.value === suggestedUnit);
                    
                    if (optionExists) {
                        // Auto-select the intelligent unit
                        unitSelect.value = suggestedUnit;
                        
                        // Show notification about auto-selection
                        showUnitAutoSelectionMessage(suggestedUnit);
                    } else {
                        console.warn('Suggested unit not found in dropdown:', suggestedUnit);
                    }
                }
            })
            .catch(error => {
                console.error('Error getting intelligent unit:', error);
            });
    }
    
    // Show unit auto-selection notification
    function showUnitAutoSelectionMessage(unit) {
        const unitNames = {
            'pcs': 'Pieces',
            'unit': 'Unit', 
            'set': 'Set',
            'box': 'Box',
            'kg': 'Kilogram',
            'm': 'Meter',
            'm3': 'Cubic Meter',
            'sqm': 'Square Meter',
            'l': 'Liter',
            'lot': 'Lot'
        };
        
        const unitName = unitNames[unit] || unit;
        
        // Create or update notification
        let notification = document.getElementById('unit-auto-selection-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'unit-auto-selection-notification';
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.style.top = '70px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '250px';
            document.body.appendChild(notification);
        }
        
        notification.innerHTML = `
            <i class="bi bi-gear me-2"></i>Unit auto-selected: ${unitName}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Show auto-selection success message
    function showAutoSelectionMessage(message) {
        // Create or update notification
        let notification = document.getElementById('auto-selection-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'auto-selection-notification';
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            document.body.appendChild(notification);
        }
        
        notification.innerHTML = `
            <i class="bi bi-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Initialize Quick Entry functionality
    initializeQuickEntry();
});

// Initialize Quick Entry functionality for create form
function initializeQuickEntry() {
    // Add quick entry buttons to the quick entry container
    const quickEntryContainer = document.getElementById('quick-entry-container');
    console.log('Initializing quick entry - Container found:', !!quickEntryContainer);
    
    if (quickEntryContainer) {
        const quickEntryHtml = `
        <div class="card bg-light border-primary mb-4">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Asset Quick Entry
                    <small class="ms-2 opacity-75">Click to auto-fill common items</small>
                </h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Hand Tool">
                            🔨<br><small>Hand Tool</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Power Tool">
                            🪚<br><small>Power Tool</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Measurement">
                            📏<br><small>Measurement</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Safety">
                            🦺<br><small>Safety</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Material">
                            🧱<br><small>Material</small>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-fill" data-equipment="Equipment">
                            ⚙️<br><small>Equipment</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;
        
        quickEntryContainer.innerHTML = quickEntryHtml;
        console.log('Quick entry buttons added to create form');
    } else {
        console.warn('Quick entry container not found - buttons not added');
    }
}

// Quick fill equipment type for create form
function quickFillEquipment(equipmentName) {
    console.log('Quick fill equipment called with:', equipmentName);
    const equipmentSelect = document.getElementById('equipment_type_id');
    
    if (!equipmentSelect) {
        console.error('Equipment select not found');
        return;
    }
    
    console.log('Equipment select found, options count:', equipmentSelect.options.length);
    
    // Find the equipment type option
    let found = false;
    for (let option of equipmentSelect.options) {
        console.log('Checking option:', option.textContent, 'against', equipmentName);
        if (option.textContent.toLowerCase().includes(equipmentName.toLowerCase())) {
            console.log('Match found! Setting value:', option.value);
            
            // Clear the Select2 first if it exists
            if (window.jQuery && window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#equipment_type_id').val(option.value).trigger('change');
            } else {
                equipmentSelect.value = option.value;
                equipmentSelect.dispatchEvent(new Event('change'));
            }
            
            // Wait for category auto-selection and subtype loading, then try to generate name
            setTimeout(() => {
                const subtypeSelect = document.getElementById('subtype_id');
                if (subtypeSelect && subtypeSelect.options.length > 1) {
                    // Auto-select first available subtype for name generation
                    const firstSubtype = subtypeSelect.options[1]; // Skip empty option
                    if (firstSubtype) {
                        console.log('Auto-selecting first subtype for name generation:', firstSubtype.textContent);
                        
                        // Set value using Select2 if available, otherwise native
                        if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                            window.jQuery('#subtype_id').val(firstSubtype.value).trigger('change');
                        } else {
                            subtypeSelect.value = firstSubtype.value;
                            subtypeSelect.dispatchEvent(new Event('change'));
                        }
                    }
                }
            }, 1000); // Wait 1 second for category auto-selection and subtype loading
            
            // Show success message
            showAutoSelectionMessage(`Quick filled: ${equipmentName} - Name will be generated automatically!`);
            found = true;
            break;
        }
    }
    
    if (!found) {
        console.warn(`No equipment type found matching: ${equipmentName}`);
        console.log('Available options:');
        for (let option of equipmentSelect.options) {
            if (option.value) console.log(' - ', option.textContent);
        }
    }
}

// Global test function for create form
window.testQuickEntry = function(equipmentName) {
    console.log('Testing quick entry with:', equipmentName || 'Hand Tool');
    quickFillEquipment(equipmentName || 'Hand Tool');
};

// Event delegation for clear buttons and quick entry (create form)
document.addEventListener('click', function(e) {
    // Handle clear category button
    if (e.target && e.target.id === 'clear-category-btn') {
        console.log('Clear category button clicked via delegation (create form)');
        e.preventDefault();
        e.stopPropagation();
        
        // Clear both fields
        $('#category_id').val('').trigger('change');
        $('#equipment_type_id').val('').trigger('change');
        
        // Clear other fields
        $('#subtype_id').val('').trigger('change');
        hideNamePreview();
        $('#unit').val('pcs');
        
        // Show notification
        showAutoSelectionMessage('All selections cleared - showing all equipment types');
        return false;
    }
    
    // Handle clear equipment button
    if (e.target && e.target.id === 'clear-equipment-btn') {
        console.log('Clear equipment button clicked via delegation (create form)');
        e.preventDefault();
        e.stopPropagation();
        
        // Set flag to prevent auto-category selection
        window.preventCategoryAutoSelection = true;
        
        // Clear both fields (bidirectional clearing)
        $('#category_id').val('').trigger('change');
        $('#equipment_type_id').val('').trigger('change');
        
        // Clear other fields
        $('#subtype_id').val('').trigger('change');
        hideNamePreview();
        $('#unit').val('pcs');
        
        // Reset flag after processing
        setTimeout(() => {
            window.preventCategoryAutoSelection = false;
        }, 200);
        
        showAutoSelectionMessage('Equipment and category cleared - showing all equipment types');
        return false;
    }
    
    // Handle quick entry buttons
    if (e.target && e.target.classList.contains('quick-fill')) {
        console.log('Quick fill button clicked via delegation (create form)');
        e.preventDefault();
        e.stopPropagation();
        
        const equipmentName = e.target.dataset.equipment;
        if (equipmentName) {
            console.log('Quick filling equipment:', equipmentName);
            quickFillEquipment(equipmentName);
        } else {
            console.error('No equipment name found in dataset');
        }
        return false;
    }
});

</script>

<!-- Asset Standardizer JavaScript -->
<script src="/assets/js/asset-standardizer.js"></script>

<script>
// Additional initialization for asset standardizer
document.addEventListener('DOMContentLoaded', function() {
    // Show learning section if needed
    const showLearning = localStorage.getItem('asset_show_learning') === 'true';
    if (showLearning) {
        const learningSection = document.getElementById('learning-section');
        if (learningSection) {
            learningSection.style.display = 'block';
        }
    }
    
    // Auto-fill available quantity when quantity changes
    const quantityInput = document.getElementById('quantity');
    const availableQuantityInput = document.getElementById('available_quantity');
    
    if (quantityInput && availableQuantityInput) {
        quantityInput.addEventListener('input', function() {
            if (!availableQuantityInput.value || availableQuantityInput.value === '0') {
                availableQuantityInput.value = this.value;
            }
        });
    }
    
    // Help button functionality
    const helpBtn = document.getElementById('help-btn');
    if (helpBtn) {
        helpBtn.addEventListener('click', function() {
            const helpContent = `
                <div class="row">
                    <div class="col-12">
                        <h6><i class="bi bi-info-circle me-2"></i>Asset Creation Help</h6>
                        <p>This form includes intelligent features to help you create standardized assets:</p>
                        
                        <h6><i class="bi bi-spell-check me-2"></i>Smart Asset Names</h6>
                        <ul>
                            <li>Start typing an asset name to see suggestions</li>
                            <li>The system will detect and correct common spelling mistakes</li>
                            <li>Green icon means the asset is recognized</li>
                            <li>Yellow icon means partial match - please verify</li>
                        </ul>
                        
                        <h6><i class="bi bi-building me-2"></i>Brand Standardization</h6>
                        <ul>
                            <li>Brand names are automatically standardized (e.g., "dewalt" becomes "DeWalt")</li>
                            <li>The system suggests similar brands if exact match is not found</li>
                        </ul>
                        
                        <h6><i class="bi bi-diagram-3 me-2"></i>Multi-Disciplinary Classification</h6>
                        <ul>
                            <li>Select which engineering disciplines use this asset</li>
                            <li>This helps with better organization and searchability</li>
                        </ul>
                        
                        <h6><i class="bi bi-lightbulb me-2"></i>Help the System Learn</h6>
                        <ul>
                            <li>If you notice incorrect suggestions, use the learning section</li>
                            <li>Your corrections help improve the system for everyone</li>
                        </ul>
                    </div>
                </div>
            `;
            
            // Use the existing modal function from asset-standardizer.js
            if (window.assetStandardizer) {
                window.assetStandardizer.showModal('Asset Creation Help', helpContent);
            }
        });
    }
    
    // Initialize discipline handling
    initializeDisciplineHandling();
    
    // Initialize brand validation
    initializeBrandValidation();
});

// Discipline handling functions
function initializeDisciplineHandling() {
    const categorySelect = document.getElementById('category_id');
    const disciplineSection = document.getElementById('discipline-section');

    if (!categorySelect || !disciplineSection) return;

    // Load disciplines when category changes
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        if (categoryId) {
            loadDisciplinesForCategory(categoryId);
        } else {
            disciplineSection.style.display = 'none';
            clearDisciplines();
        }
    });

    // Load all disciplines on page load
    loadAllDisciplines();

    // If category is already selected, load disciplines
    if (categorySelect.value) {
        loadDisciplinesForCategory(categorySelect.value);
    } else {
        // Show disciplines section by default for all categories
        disciplineSection.style.display = 'block';
        // Retry loading if data not available yet
        setTimeout(() => {
            if (allDisciplines.length === 0) {
                loadAllDisciplines();
            }
            populateAllDisciplines();
        }, 500);
    }
}

let allDisciplines = [];

function loadAllDisciplines() {
    console.log('Starting disciplines API call...');
    fetch('?route=api/assets/disciplines&action=list', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRFTokenValue
        }
    })
    .then(response => {
        console.log('API response status:', response.status);
        console.log('API response headers:', response.headers.get('content-type'));
        return response.text(); // Get raw response first
    })
    .then(text => {
        console.log('Raw API response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                allDisciplines = data.data;
                console.log('✓ Disciplines loaded successfully:', allDisciplines.length, 'disciplines');
                console.log('Sample disciplines:', allDisciplines.slice(0, 3));
                
                // Try to populate immediately
                populateAllDisciplines();
            } else {
                console.error('❌ Disciplines API error:', data.message);
            }
        } catch (parseError) {
            console.error('❌ JSON parse error:', parseError);
            console.error('Response was not valid JSON:', text);
        }
    })
    .catch(error => {
        console.error('❌ Network error loading disciplines:', error);
    });
}

function loadDisciplinesForCategory(categoryId) {
    const disciplineSection = document.getElementById('discipline-section');
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');
    
    // Show loading state
    if (disciplineCheckboxes) {
        disciplineCheckboxes.innerHTML = '<div class="text-muted"><i class="bi bi-arrow-clockwise"></i> Loading disciplines...</div>';
    }
    
    fetch(`?route=api/assets/disciplines&action=by_category&category_id=${categoryId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRFTokenValue
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            disciplineSection.style.display = 'block';
            populateDisciplines(data.data);
        } else {
            // If no specific disciplines for category, show all main disciplines
            disciplineSection.style.display = 'block';
            populateAllDisciplines();
        }
    })
    .catch(error => {
        console.warn('Could not load disciplines for category:', error);
        // Fallback to showing all disciplines
        disciplineSection.style.display = 'block';
        populateAllDisciplines();
    });
}

function populateDisciplines(disciplines) {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (!primaryDisciplineSelect || !disciplineCheckboxes) return;

    // Get currently selected primary discipline
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Clear existing options
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';
    disciplineCheckboxes.innerHTML = '';

    // Populate primary discipline dropdown
    disciplines.forEach(discipline => {
        const option = document.createElement('option');
        option.value = discipline.id;
        option.textContent = discipline.name;
        if (discipline.has_primary_use) {
            option.textContent += ' (Recommended)';
        }
        primaryDisciplineSelect.appendChild(option);
    });

    // Restore previously selected primary discipline
    if (selectedPrimaryId) {
        primaryDisciplineSelect.value = selectedPrimaryId;
    }

    // Only populate checkboxes if primary discipline is selected
    if (selectedPrimaryId) {
        disciplines.forEach(discipline => {
            // Skip this discipline if it's selected as primary
            if (discipline.id == selectedPrimaryId) {
                return;
            }

            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check mb-2';

            const checkbox = document.createElement('input');
            checkbox.className = 'form-check-input';
            checkbox.type = 'checkbox';
            checkbox.id = `discipline_${discipline.id}`;
            checkbox.name = 'disciplines[]';
            checkbox.value = discipline.id;

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.setAttribute('for', checkbox.id);
            label.innerHTML = `
                <strong>${discipline.name}</strong>
                ${discipline.usage_count ? `<small class="text-muted d-block">(${discipline.usage_count} related tools)</small>` : ''}
            `;

            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            disciplineCheckboxes.appendChild(checkboxDiv);
        });
    } else {
        // Show message when no primary is selected
        disciplineCheckboxes.innerHTML = '<div class="text-muted"><i class="bi bi-info-circle me-2"></i>Please select a Primary Discipline first</div>';
    }
}

function populateAllDisciplines() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (!primaryDisciplineSelect || !disciplineCheckboxes) return;

    if (!allDisciplines.length) {
        disciplineCheckboxes.innerHTML = '<div class="text-muted">Loading disciplines...</div>';
        return;
    }

    // Get currently selected primary discipline BEFORE clearing
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Clear and repopulate primary dropdown
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';

    // Add all disciplines to primary dropdown
    allDisciplines.forEach(discipline => {
        const option = document.createElement('option');
        option.value = discipline.id;
        option.textContent = discipline.name;
        primaryDisciplineSelect.appendChild(option);

        // Add sub-disciplines to dropdown
        if (discipline.children && discipline.children.length > 0) {
            discipline.children.forEach(child => {
                const childOption = document.createElement('option');
                childOption.value = child.id;
                childOption.textContent = `  ${child.name}`;
                primaryDisciplineSelect.appendChild(childOption);
            });
        }
    });

    // Restore the previously selected primary discipline
    if (selectedPrimaryId) {
        primaryDisciplineSelect.value = selectedPrimaryId;
    }

    // Now populate the checkboxes based on the current selection
    populateCheckboxes(selectedPrimaryId);
}

function populateCheckboxes(excludePrimaryId) {
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');
    if (!disciplineCheckboxes) return;

    // Clear checkboxes
    disciplineCheckboxes.innerHTML = '';

    // If no primary is selected, show all checkboxes
    if (!excludePrimaryId) {
        allDisciplines.forEach(discipline => {
            // Add main discipline checkbox
            const checkboxDiv = document.createElement('div');
            checkboxDiv.className = 'form-check mb-2';

            const checkbox = document.createElement('input');
            checkbox.className = 'form-check-input';
            checkbox.type = 'checkbox';
            checkbox.id = `discipline_${discipline.id}`;
            checkbox.name = 'disciplines[]';
            checkbox.value = discipline.id;

            const label = document.createElement('label');
            label.className = 'form-check-label fw-bold';
            label.setAttribute('for', checkbox.id);
            label.textContent = discipline.name;

            checkboxDiv.appendChild(checkbox);
            checkboxDiv.appendChild(label);
            disciplineCheckboxes.appendChild(checkboxDiv);

            // Add sub-disciplines checkboxes
            if (discipline.children && discipline.children.length > 0) {
                discipline.children.forEach(child => {
                    const childDiv = document.createElement('div');
                    childDiv.className = 'form-check mb-1 ms-3';

                    const childCheckbox = document.createElement('input');
                    childCheckbox.className = 'form-check-input';
                    childCheckbox.type = 'checkbox';
                    childCheckbox.id = `discipline_${child.id}`;
                    childCheckbox.name = 'disciplines[]';
                    childCheckbox.value = child.id;

                    const childLabel = document.createElement('label');
                    childLabel.className = 'form-check-label text-muted';
                    childLabel.setAttribute('for', childCheckbox.id);
                    childLabel.textContent = child.name;

                    childDiv.appendChild(childCheckbox);
                    childDiv.appendChild(childLabel);
                    disciplineCheckboxes.appendChild(childDiv);
                });
            }
        });
    } else {
        // If primary is selected, show checkboxes EXCLUDING the primary
        allDisciplines.forEach(discipline => {
            // Add main discipline checkbox (exclude if it's the primary)
            if (discipline.id != excludePrimaryId) {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'form-check mb-2';

                const checkbox = document.createElement('input');
                checkbox.className = 'form-check-input';
                checkbox.type = 'checkbox';
                checkbox.id = `discipline_${discipline.id}`;
                checkbox.name = 'disciplines[]';
                checkbox.value = discipline.id;

                const label = document.createElement('label');
                label.className = 'form-check-label fw-bold';
                label.setAttribute('for', checkbox.id);
                label.textContent = discipline.name;

                checkboxDiv.appendChild(checkbox);
                checkboxDiv.appendChild(label);
                disciplineCheckboxes.appendChild(checkboxDiv);
            }

            // Add sub-disciplines checkboxes
            if (discipline.children && discipline.children.length > 0) {
                discipline.children.forEach(child => {
                    // Add checkbox (exclude if it's the primary)
                    if (child.id != excludePrimaryId) {
                        const childDiv = document.createElement('div');
                        childDiv.className = 'form-check mb-1 ms-3';

                        const childCheckbox = document.createElement('input');
                        childCheckbox.className = 'form-check-input';
                        childCheckbox.type = 'checkbox';
                        childCheckbox.id = `discipline_${child.id}`;
                        childCheckbox.name = 'disciplines[]';
                        childCheckbox.value = child.id;

                        const childLabel = document.createElement('label');
                        childLabel.className = 'form-check-label text-muted';
                        childLabel.setAttribute('for', childCheckbox.id);
                        childLabel.textContent = child.name;

                        childDiv.appendChild(childCheckbox);
                        childDiv.appendChild(childLabel);
                        disciplineCheckboxes.appendChild(childDiv);
                    }
                });
            }
        });
    }
}

function clearDisciplines() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    const disciplineCheckboxes = document.getElementById('discipline-checkboxes');

    if (primaryDisciplineSelect) {
        primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use</option>';
    }

    if (disciplineCheckboxes) {
        disciplineCheckboxes.innerHTML = '';
    }
}

function updateDisciplineCheckboxes() {
    const primaryDisciplineSelect = document.getElementById('primary_discipline');
    if (!primaryDisciplineSelect) return;

    // Get the newly selected primary discipline
    const selectedPrimaryId = primaryDisciplineSelect.value;

    // Repopulate checkboxes excluding the selected primary
    if (allDisciplines && allDisciplines.length > 0) {
        populateCheckboxes(selectedPrimaryId);
    }
}

// Brand validation functions
function initializeBrandValidation() {
    const brandInput = document.getElementById('brand');
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');
    
    if (!brandInput) return;
    
    let brandValidationTimeout;
    
    brandInput.addEventListener('input', function() {
        const brand = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(brandValidationTimeout);
        
        if (brand.length === 0) {
            resetBrandStatus();
            return;
        }
        
        // Update icon to loading state
        brandIcon.className = 'bi bi-arrow-clockwise text-primary';
        brandFeedback.textContent = 'Validating brand...';
        brandFeedback.className = 'form-text text-muted';
        
        // Set timeout for validation
        brandValidationTimeout = setTimeout(() => {
            validateBrand(brand);
        }, 500);
    });
}

function validateBrand(brand) {
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');
    
    fetch('?route=api/assets/validate-brand', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRFTokenValue
        },
        body: JSON.stringify({ brand: brand })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'verified') {
                // Brand is verified
                brandIcon.className = 'bi bi-check-circle text-success';
                brandFeedback.innerHTML = `<i class="bi bi-check-circle text-success me-1"></i>Verified brand: ${data.standardized_name}`;
                brandFeedback.className = 'form-text text-success';
                
                // Update hidden fields
                document.getElementById('standardized_brand').value = data.standardized_name;
                document.getElementById('brand_id').value = data.brand_id;
                
            } else if (data.status === 'unknown') {
                // Brand is unknown - offer suggestion option
                brandIcon.className = 'bi bi-question-circle text-warning';
                brandFeedback.innerHTML = `
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                    Unknown brand. Would you like to 
                    <button type="button" class="btn btn-link p-0 align-baseline" onclick="suggestBrand('${brand}')" style="font-size: inherit;">
                        suggest it for review?
                    </button>
                `;
                brandFeedback.className = 'form-text text-warning';
                
                // Clear hidden fields
                document.getElementById('standardized_brand').value = '';
                document.getElementById('brand_id').value = '';
            }
        } else {
            // Validation error
            brandIcon.className = 'bi bi-exclamation-triangle text-warning';
            brandFeedback.innerHTML = `<i class="bi bi-exclamation-triangle text-warning me-1"></i>${data.message || 'Brand validation failed'}`;
            brandFeedback.className = 'form-text text-warning';
        }
    })
    .catch(error => {
        console.warn('Brand validation error:', error);
        brandIcon.className = 'bi bi-exclamation-triangle text-warning';
        brandFeedback.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Validation temporarily unavailable';
        brandFeedback.className = 'form-text text-warning';
    });
}

function suggestBrand(brandName) {
    const userConfirmed = confirm(
        `Would you like to suggest "${brandName}" as a new brand for review?\n\n` +
        'This will notify the Asset Director for approval. You can continue creating the asset while the brand is under review.'
    );
    
    if (userConfirmed) {
        fetch('?route=api/assets/suggest-brand', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': CSRFTokenValue
            },
            body: JSON.stringify({ 
                brand_name: brandName,
                context: 'Asset Creation Form'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update feedback to show suggestion was submitted
                const brandFeedback = document.getElementById('brand-feedback');
                const brandIcon = document.getElementById('brand-icon');
                
                brandIcon.className = 'bi bi-clock text-info';
                brandFeedback.innerHTML = `
                    <i class="bi bi-check-circle text-success me-1"></i>
                    Brand suggestion submitted for review. You can continue creating the asset.
                `;
                brandFeedback.className = 'form-text text-success';
                
                // Set temporary values for form submission
                document.getElementById('standardized_brand').value = brandName;
                document.getElementById('brand_id').value = 'pending';
            } else {
                alert('Failed to submit brand suggestion: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error suggesting brand:', error);
            alert('Failed to submit brand suggestion. Please try again.');
        });
    }
}

function resetBrandStatus() {
    const brandIcon = document.getElementById('brand-icon');
    const brandFeedback = document.getElementById('brand-feedback');
    
    if (brandIcon) brandIcon.className = 'bi bi-question-circle text-muted';
    if (brandFeedback) {
        brandFeedback.textContent = 'Start typing for brand suggestions and validation';
        brandFeedback.className = 'form-text';
    }
    
    // Clear hidden fields
    document.getElementById('standardized_brand').value = '';
    document.getElementById('brand_id').value = '';
}

// Form enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
</script>

<style>
/* Responsive form styles */
.card-body {
    padding: 1.25rem;
}

@media (max-width: 992px) {
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 768px) {
    .card-body {
        padding: 0.875rem;
    }
    
    .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.375rem;
    }
    
    .btn-toolbar {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-toolbar .btn {
        width: 100%;
    }
    
    .alert {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .card {
        margin-bottom: 1rem;
    }
    
    .card-header {
        padding: 0.75rem;
    }
    
    .card-header h6 {
        font-size: 0.95rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    /* Improve touch targets on mobile */
    .form-control, .form-select {
        min-height: 44px;
        padding: 0.5rem 0.75rem;
    }
    
    .input-group .input-group-text {
        padding: 0.5rem 0.75rem;
    }
    
    /* Stack buttons vertically on very small screens */
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
    }
}

/* Form validation and feedback */
@media (max-width: 768px) {
    .invalid-feedback, .valid-feedback {
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
    
    .form-text {
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
}

/* Discipline checkboxes responsive */
#discipline-checkboxes {
    max-height: 160px;
    padding: 1rem;
}

#discipline-checkboxes .form-check {
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
}

#discipline-checkboxes .form-check-input {
    margin-top: 0.25rem;
}

#discipline-checkboxes .form-check-label {
    font-size: 0.95rem;
    line-height: 1.4;
}

/* Multi-column layout for larger screens */
@media (min-width: 992px) {
    #discipline-checkboxes {
        columns: 2;
        column-gap: 1.25rem;
        max-height: 180px;
    }
    
    #discipline-checkboxes .form-check {
        break-inside: avoid;
        margin-bottom: 0.75rem;
    }
}

@media (min-width: 1200px) {
    #discipline-checkboxes {
        columns: 3;
        column-gap: 1.25rem;
        max-height: 180px;
    }
}

@media (max-width: 768px) {
    #discipline-checkboxes {
        max-height: 140px;
        padding: 0.75rem;
    }
    
    #discipline-checkboxes .form-check-label {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    #discipline-checkboxes {
        max-height: 120px;
        padding: 0.75rem;
    }
    
    #discipline-checkboxes .form-check {
        margin-bottom: 0.4rem;
        padding-left: 1.25rem;
    }
    
    #discipline-checkboxes .form-check-label {
        font-size: 0.85rem;
        line-height: 1.3;
    }
}

/* Input groups responsive */
@media (max-width: 576px) {
    .input-group {
        flex-wrap: nowrap;
    }
    
    .input-group .form-control {
        flex: 1;
        min-width: 0;
    }
}
</style>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
/* Custom Select2 styling for better integration */
.select2-container--bootstrap-5 .select2-selection--single {
    height: calc(2.5rem + 2px);
    padding: 0.375rem 0.75rem;
}

/* CRITICAL: Force search box to be visible */
.select2-search--dropdown {
    display: block !important;
    padding: 4px;
}

.select2-search--dropdown .select2-search__field {
    display: block !important;
    width: 100% !important;
    padding: 6px 12px !important;
    margin: 0 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: calc(2.5rem);
}

.select2-container--bootstrap-5 .select2-dropdown {
    border-color: #dee2e6;
}

.select2-container--bootstrap-5 .select2-search--dropdown {
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.5rem 0.75rem;
    width: 100%;
    font-size: 0.95rem;
    background-color: white;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.075);
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.075), 0 0 0 0.25rem rgba(13,110,253,0.25);
}

.select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field::placeholder {
    color: #6c757d;
    opacity: 1;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
}

/* Responsive adjustments for mobile */
@media (max-width: 576px) {
    .select2-container {
        width: 100% !important;
    }
    
    .select2-dropdown {
        width: 100% !important;
    }
}

/* Fix for Select2 z-index issues */
.select2-container--open {
    z-index: 1050;
}

.select2-container--bootstrap-5 .select2-selection--single:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Unified Dropdown Synchronization System -->
<script src="unified_dropdown_sync.js"></script>

<script>
// Wait for jQuery to be ready
(function checkJQuery() {
    if (typeof jQuery === 'undefined') {
        setTimeout(checkJQuery, 100);
        return;
    }
    
    // jQuery is loaded, now initialize Select2
    $(document).ready(function() {
        // Ensure Select2 is loaded
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded');
            return;
        }
        
        // Store any pre-selected values
        var selectedProject = $('#project_id').val();
        var selectedCategory = $('#category_id').val();
        
        // Initialize Select2 for all dropdowns
        
        // Category dropdown with forced search
        $('#category_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Restore selected value if any
        if (selectedCategory) {
            $('#category_id').val(selectedCategory).trigger('change.select2');
        }
    
        // Project dropdown with forced search
        $('#project_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Project',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Restore selected value if any
        if (selectedProject) {
            $('#project_id').val(selectedProject).trigger('change.select2');
        }
        
        // Brand dropdown
        $('#brand').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search brands...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Update hidden brand_id field when brand is selected
        $('#brand').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var brandId = selectedOption.data('brand-id') || '';
            var brandName = selectedOption.val();
            
            $('#brand_id').val(brandId);
            $('#standardized_brand').val(brandName);
        });
        
        // Primary discipline dropdown
        $('#primary_discipline').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search disciplines...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });

        // Update checkboxes when primary discipline changes
        $('#primary_discipline').on('change', function() {
            updateDisciplineCheckboxes();
        });
        
        // Vendor dropdown
        $('#vendor_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search vendors...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Procurement order dropdown
        $('#procurement_order_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search orders...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Procurement item dropdown
        $('#procurement_item_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search items...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    
        // Preserve existing change handlers
        $('#category_id').on('change', function() {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        $('#procurement_order_id').on('change', function() {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        // Bidirectional clearing - handle Select2 clear events
        $('#category_id').on('select2:clear', function() {
            console.log('Category cleared via Select2 - clearing equipment type');
            // Clear equipment type when category is cleared
            $('#equipment_type_id').val('').trigger('change');
            // Clear subtypes and name preview
            $('#subtype_id').val('');
            hideNamePreview();
            // Clear unit field
            $('#unit').val('');
        });
        
        $('#equipment_type_id').on('select2:clear', function() {
            console.log('Equipment type cleared via Select2 - clearing category');
            // Set flag to prevent auto-category selection during clearing
            window.preventCategoryAutoSelection = true;
            // Clear category when equipment type is cleared
            $('#category_id').val('').trigger('change');
            // Clear subtypes and name preview
            $('#subtype_id').val('');
            hideNamePreview();
            // Clear unit field
            $('#unit').val('');
            // Reset the flag after processing
            setTimeout(() => {
                window.preventCategoryAutoSelection = false;
            }, 100);
        });
        
        // Force search box to be visible and focused on open
        $(document).on('select2:open', function(e) {
            setTimeout(function() {
                var searchField = $('.select2-container--open .select2-search--dropdown .select2-search__field');
                if (searchField.length > 0) {
                    searchField.focus();
                }
            }, 0);
        });
        
        // Debug: Check if Select2 is working
        console.log('Select2 initialized for dropdowns');
    });
})();
</script>

<?php endif; ?>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
