<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<div class="d-flex justify-content-end align-items-center mb-4 gap-2">
    <a href="?route=assets/view&id=<?= $asset['id'] ?? 0 ?>" class="btn btn-outline-info btn-sm">
        <i class="bi bi-eye me-1"></i>
        <span class="d-none d-sm-inline">View Asset</span>
        <span class="d-sm-none">View</span>
    </a>
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

<div class="row">
    <div class="col-lg-8">
        <!-- Asset Edit Form -->
        <?php if (!in_array($user['role_name'], $roleConfig['assets/edit'] ?? [])): ?>
            <div class="alert alert-danger mt-4">You do not have permission to edit this asset.</div>
        <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=assets/edit&id=<?= $asset['id'] ?>" id="assetEditForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ref" class="form-label">Asset Reference</label>
                                <input type="text" class="form-control" id="ref" 
                                       value="<?= htmlspecialchars($asset['ref']) ?>" readonly>
                                <div class="form-text">Asset reference cannot be changed</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($asset['name']) ?>" 
                                           list="asset-suggestions"
                                           autocomplete="off"
                                           data-validation="asset-name"
                                           required>
                                    <span class="input-group-text" id="name-status">
                                        <i class="bi bi-question-circle text-muted" id="name-icon" title="Asset name validation"></i>
                                    </span>
                                </div>
                                <datalist id="asset-suggestions"></datalist>
                                <div id="name-feedback" class="form-text">
                                    <?php if (!empty($asset['standardized_name']) && $asset['standardized_name'] !== $asset['name']): ?>
                                        Current standardized name: <strong><?= htmlspecialchars($asset['standardized_name']) ?></strong>
                                    <?php else: ?>
                                        Editing will validate and standardize the name
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Name Regeneration (for assets with equipment classification) -->
                                <div class="alert alert-info mt-2 border-0 bg-light" id="name-regeneration" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-magic text-info me-2"></i>
                                        <div class="flex-grow-1">
                                            <strong>Regenerate Name:</strong>
                                            <div id="preview-regenerated-name" class="text-info mt-1 fw-bold"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-info" id="apply-regenerated-name">
                                            Use New Name
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="spelling-alert" class="alert alert-warning d-none mt-2 py-2" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-spell-check me-2"></i>
                                        <span id="spelling-message"></span>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="accept-suggestion">
                                            Accept
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="dismiss-suggestion">
                                            Dismiss
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter the asset name.
                                </div>
                                <!-- Hidden fields for standardization -->
                                <input type="hidden" id="standardized_name" name="standardized_name" value="<?= htmlspecialchars($asset['standardized_name'] ?? '') ?>">
                                <input type="hidden" id="original_name" name="original_name" value="<?= htmlspecialchars($asset['original_name'] ?? $asset['name']) ?>">
                                <input type="hidden" id="asset_type_id" name="asset_type_id" value="<?= htmlspecialchars($asset['asset_type_id'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Detailed description of the asset..."><?= htmlspecialchars($asset['description'] ?? '') ?></textarea>
                            </div>
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
                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    Category <span class="text-danger">*</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-category-btn" title="Clear category to see all item types">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </button>
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    <?= $asset['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a category.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipment Classification (for legacy assets and detailed classification) -->
                    <div class="row mb-3" id="equipment-classification">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="equipment_type_id" class="form-label">
                                    Item Type
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-equipment-btn" title="Clear equipment selection">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </label>
                                <select class="form-select" id="equipment_type_id" name="equipment_type_id">
                                    <option value="">Select Item Type</option>
                                </select>
                                <div class="form-text">Specific item type for legacy assets</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subtype_id" class="form-label">Item Subtype</label>
                                <select class="form-select" id="subtype_id" name="subtype_id">
                                    <option value="">Select Subtype</option>
                                </select>
                                <div class="form-text">Detailed subtype classification</div>
                            </div>
                        </div>
                    </div>

                    <!-- Discipline Classification -->
                    <div class="row mb-4" id="discipline-section">
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-diagram-3 me-2"></i>Discipline Classification</h6>
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <div class="mb-3">
                                <label for="primary_discipline" class="form-label">Primary Discipline</label>
                                <select class="form-select" id="primary_discipline" name="primary_discipline">
                                    <option value="">Select Primary Use</option>
                                </select>
                                <div class="form-text">Main discipline where this asset was used</div>
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

                    <!-- Basic Asset Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php if (!empty($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" 
                                                    <?= $asset['project_id'] == $project['id'] ? 'selected' : '' ?>>
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
                    
                    <!-- Vendor Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-building me-1"></i>Vendor Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vendor_id" class="form-label">Vendor</label>
                                <select class="form-select" id="vendor_id" name="vendor_id">
                                    <option value="">Select Vendor</option>
                                    <?php if (!empty($vendors)): ?>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= $vendor['id'] ?>" 
                                                    <?= $asset['vendor_id'] == $vendor['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vendor['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maker_id" class="form-label">Manufacturer</label>
                                <select class="form-select" id="maker_id" name="maker_id">
                                    <option value="">Select Manufacturer</option>
                                    <?php if (!empty($makers)): ?>
                                        <?php foreach ($makers as $maker): ?>
                                            <option value="<?= $maker['id'] ?>" 
                                                    <?= $asset['maker_id'] == $maker['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($maker['name']) ?>
                                                <?php if (!empty($maker['country'])): ?>
                                                    (<?= htmlspecialchars($maker['country']) ?>)
                                                <?php endif; ?>
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
                                                    <?= $asset['client_id'] == $client['id'] ? 'selected' : '' ?>>
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
                                       <?= $asset['is_client_supplied'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_client_supplied">
                                    Client Supplied Asset
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Asset Details -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Asset Details
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand/Manufacturer</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">Select Brand</option>
                                    <?php if (!empty($brands)): ?>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= htmlspecialchars($brand['official_name']) ?>" 
                                                    data-brand-id="<?= $brand['id'] ?>"
                                                    <?= ($asset['brand_id'] ?? '') == $brand['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand['official_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Manufacturer or brand name</div>
                                <!-- Hidden fields for brand standardization -->
                                <input type="hidden" id="standardized_brand" name="standardized_brand" value="<?= htmlspecialchars($asset['standardized_brand'] ?? '') ?>">
                                <input type="hidden" id="brand_id" name="brand_id" value="<?= htmlspecialchars($asset['brand_id'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?= htmlspecialchars($asset['quantity'] ?? '1') ?>" 
                                       min="1" max="9999">
                                <div class="form-text">Number of items</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <select class="form-select" id="unit" name="unit">
                                    <option value="pcs" <?= ($asset['unit'] ?? 'pcs') === 'pcs' ? 'selected' : '' ?>>Pieces</option>
                                    <option value="unit" <?= ($asset['unit'] ?? '') === 'unit' ? 'selected' : '' ?>>Unit</option>
                                    <option value="set" <?= ($asset['unit'] ?? '') === 'set' ? 'selected' : '' ?>>Set</option>
                                    <option value="box" <?= ($asset['unit'] ?? '') === 'box' ? 'selected' : '' ?>>Box</option>
                                    <option value="kg" <?= ($asset['unit'] ?? '') === 'kg' ? 'selected' : '' ?>>Kilogram</option>
                                    <option value="m" <?= ($asset['unit'] ?? '') === 'm' ? 'selected' : '' ?>>Meter</option>
                                    <option value="m3" <?= ($asset['unit'] ?? '') === 'm3' ? 'selected' : '' ?>>Cubic Meter</option>
                                    <option value="sqm" <?= ($asset['unit'] ?? '') === 'sqm' ? 'selected' : '' ?>>Square Meter</option>
                                    <option value="l" <?= ($asset['unit'] ?? '') === 'l' ? 'selected' : '' ?>>Liter</option>
                                    <option value="lot" <?= ($asset['unit'] ?? '') === 'lot' ? 'selected' : '' ?>>Lot</option>
                                </select>
                                <div class="form-text">Unit of measurement</div>
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
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" 
                                       value="<?= htmlspecialchars($asset['model'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                       value="<?= htmlspecialchars($asset['serial_number'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="specifications" class="form-label">Detailed Specifications</label>
                                <textarea class="form-control" id="specifications" name="specifications" rows="3"
                                          placeholder="Technical specifications, dimensions, capacity, etc..."><?= htmlspecialchars($asset['specifications'] ?? '') ?></textarea>
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
                                       value="<?= $asset['acquired_date'] ?>" required>
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
                                           step="0.01" min="0" value="<?= $asset['acquisition_cost'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                           step="0.01" min="0" value="<?= $asset['unit_cost'] ?? '' ?>">
                                </div>
                                <div class="form-text">Individual unit cost if different from total</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                       value="<?= $asset['warranty_expiry'] ?? '' ?>">
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
                                       value="<?= htmlspecialchars($asset['location'] ?? '') ?>"
                                       placeholder="Warehouse, Site office, etc.">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="condition_notes" class="form-label">Condition Notes</label>
                                <textarea class="form-control" id="condition_notes" name="condition_notes" rows="2"
                                          placeholder="Current condition, any defects, etc..."><?= htmlspecialchars($asset['condition_notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Current Status -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <?php
                $statusClasses = [
                    'available' => 'bg-success',
                    'in_use' => 'bg-primary',
                    'borrowed' => 'bg-info',
                    'under_maintenance' => 'bg-warning',
                    'retired' => 'bg-secondary',
                    'disposed' => 'bg-dark'
                ];
                $statusClass = $statusClasses[$asset['status']] ?? 'bg-secondary';
                ?>
                <span class="badge <?= $statusClass ?> fs-6 mb-3">
                    <?= ucfirst(str_replace('_', ' ', $asset['status'])) ?>
                </span>
                
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Created:</span>
                        <span><?= date('M j, Y', strtotime($asset['created_at'])) ?></span>
                    </div>
                    <?php if ($asset['updated_at']): ?>
                        <div class="d-flex justify-content-between">
                            <span>Last Updated:</span>
                            <span><?= date('M j, Y', strtotime($asset['updated_at'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Guidelines -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Edit Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Asset reference cannot be changed
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Required fields must be filled
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Changes are logged for audit
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Status changes require separate action
                    </li>
                    <li class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Project changes may affect availability
                    </li>
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
                    <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Asset Details
                    </a>
                    
                    <?php if ($asset['status'] === 'available'): ?>
                        <?php if ($auth->hasRole(['System Admin', 'Project Manager', 'Site Inventory Clerk'])): ?>
                            <a href="?route=withdrawals/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-box-arrow-right me-1"></i>Withdraw Asset
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($auth->hasRole(['System Admin', 'Asset Director'])): ?>
                        <a href="?route=maintenance/create&asset_id=<?= $asset['id'] ?>" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-tools me-1"></i>Schedule Maintenance
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Unified Dropdown Synchronization System -->
<script src="unified_dropdown_sync.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('assetEditForm');
    const acquisitionCostInput = document.getElementById('acquisition_cost');
    const unitCostInput = document.getElementById('unit_cost');
    
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
    
    // Initialize all functionality in correct order
    console.log('Starting edit form initialization...');
    
    // Step 1: Initialize basic functionality
    initializeClearButtons();
    
    // Step 2: Load dynamic data and equipment classification
    initializeEquipmentClassification();
    
    // Step 3: Initialize Select2 after data is loaded
    setTimeout(() => {
        console.log('Initializing Select2 after data loading...');
        initializeSelect2();
        
        // Step 4: Reinitialize Select2 for dynamically populated dropdowns
        setTimeout(() => {
            reinitializeSelect2ForDynamicDropdowns();
        }, 1000);
    }, 500);
});

// Equipment classification functions
function initializeEquipmentClassification() {
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    
    // Load item types when category changes
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            loadEquipmentTypes(categoryId);
        });
        
        // Load initial item types if category is already selected
        if (categorySelect.value) {
            loadEquipmentTypes(categorySelect.value);
        }
    }
    
    // Equipment type change handler with category auto-population
    if (equipmentTypeSelect) {
        equipmentTypeSelect.addEventListener('change', function() {
            const equipmentTypeId = this.value;
            console.log('Edit form: Equipment type changed to:', equipmentTypeId);
            
            if (equipmentTypeId) {
                // Use the new API to get category info and auto-populate
                fetch(`?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Edit form: API response:', data);
                        if (data.success && data.data && data.data.category_id) {
                            const currentCategoryId = categorySelect.value;
                            const targetCategoryId = data.data.category_id;
                            
                            console.log('Edit form: Current category:', currentCategoryId, 'Target:', targetCategoryId);
                            
                            if (!currentCategoryId || currentCategoryId != targetCategoryId) {
                                console.log('Edit form: Auto-selecting category:', targetCategoryId, data.data.category_name);
                                
                                // Set category value
                                categorySelect.value = targetCategoryId;
                                
                                // Trigger Select2 update if applicable
                                if (window.jQuery && window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                                    window.jQuery('#category_id').val(targetCategoryId).trigger('change');
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Edit form: Error loading item type details:', error);
                    });
                
                // Continue with existing functionality
                loadSubtypes(equipmentTypeId);
                
                // Trigger name regeneration and unit auto-population
                setTimeout(() => {
                    tryRegenerateName();
                    updateIntelligentUnit(equipmentTypeId);
                }, 500);
            }
        });
    }
    
    // Initialize dropdowns with existing data on page load
    setTimeout(() => {
        console.log('Initializing edit form dropdowns with existing data...');
        if (categorySelect && categorySelect.value) {
            console.log('Loading item types for existing category:', categorySelect.value);
            loadEquipmentTypes(categorySelect.value);
        }
        if (equipmentTypeSelect && equipmentTypeSelect.value) {
            loadSubtypes(equipmentTypeSelect.value);
        }
    }, 500);
    
    // Regenerate name when subtype changes
    if (subtypeSelect) {
        subtypeSelect.addEventListener('change', function() {
            if (this.value && equipmentTypeSelect.value) {
                const equipmentTypeId = equipmentTypeSelect.value;
                const subtypeId = this.value;
                setTimeout(() => {
                    tryRegenerateName();
                    updateIntelligentUnit(equipmentTypeId, subtypeId);
                }, 500);
            }
        });
    }
    
    // Initialize name regeneration button
    const applyNameBtn = document.getElementById('apply-regenerated-name');
    if (applyNameBtn) {
        applyNameBtn.addEventListener('click', function() {
            const nameInput = document.getElementById('name');
            const previewName = document.getElementById('preview-regenerated-name').textContent;
            if (previewName && nameInput) {
                nameInput.value = previewName;
                hideNameRegeneration();
                console.log('Applied regenerated name:', previewName);
            }
        });
    }
}

// Load item types based on category
function loadEquipmentTypes(categoryId) {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    
    if (!categoryId) {
        equipmentTypeSelect.innerHTML = '<option value="">Select Item Type</option>';
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        return;
    }
    
    // Clear existing options
    equipmentTypeSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`?route=api/equipment-types&category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            equipmentTypeSelect.innerHTML = '<option value="">Select Item Type</option>';
            
            if (data.success && data.data) {
                data.data.forEach(equipmentType => {
                    const option = document.createElement('option');
                    option.value = equipmentType.id;
                    option.textContent = equipmentType.name;
                    
                    // Check if this item type should be selected
                    const currentEquipmentTypeId = '<?= $asset['equipment_type_id'] ?? '' ?>';
                    if (currentEquipmentTypeId && equipmentType.id == currentEquipmentTypeId) {
                        option.selected = true;
                    }
                    
                    equipmentTypeSelect.appendChild(option);
                });
                
                // Reinitialize Select2 for item type after loading options
                setTimeout(() => {
                    if (window.jQuery && equipmentTypeSelect.options.length > 1) {
                        const $equipmentSelect = window.jQuery('#equipment_type_id');
                        if ($equipmentSelect.hasClass('select2-hidden-accessible')) {
                            $equipmentSelect.select2('destroy');
                        }
                        $equipmentSelect.select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Select Item Type',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });
                        
                        // Reattach event handlers
                        $equipmentSelect.on('select2:select', function() {
                            this.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                        
                        $equipmentSelect.on('select2:clear', function() {
                            console.log('Equipment type cleared via Select2 in edit form');  
                            window.jQuery('#subtype_id').val('').trigger('change');
                        });
                    }
                }, 100);
                
                // If we had a selected item type, load its subtypes
                if (equipmentTypeSelect.value) {
                    loadSubtypes(equipmentTypeSelect.value);
                }
            }
        })
        .catch(error => {
            console.error('Error loading item types:', error);
            equipmentTypeSelect.innerHTML = '<option value="">Error loading item types</option>';
        });
}

// Load subtypes based on item type
function loadSubtypes(equipmentTypeId) {
    const subtypeSelect = document.getElementById('subtype_id');
    
    if (!equipmentTypeId) {
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        return;
    }
    
    // Clear existing options
    subtypeSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`?route=api/subtypes&equipment_type_id=${equipmentTypeId}`)
        .then(response => response.json())
        .then(data => {
            subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
            
            if (data.success && data.data) {
                if (data.data.length === 0) {
                    // No subtypes available - show helpful message
                    subtypeSelect.innerHTML = '<option value="">No subtypes available for this item type</option>';
                    console.log('🔧 No subtypes found for item type:', equipmentTypeId);
                    return;
                }
                
                data.data.forEach(subtype => {
                    const option = document.createElement('option');
                    option.value = subtype.id;
                    option.textContent = subtype.subtype_name;
                    
                    // Check if this subtype should be selected
                    const currentSubtypeId = '<?= $asset['subtype_id'] ?? '' ?>';
                    if (currentSubtypeId && subtype.id == currentSubtypeId) {
                        option.selected = true;
                    }
                    
                    subtypeSelect.appendChild(option);
                });
                
                // Reinitialize Select2 for subtype after loading options
                setTimeout(() => {
                    if (window.jQuery && subtypeSelect.options.length > 1) {
                        const $subtypeSelect = window.jQuery('#subtype_id');
                        if ($subtypeSelect.hasClass('select2-hidden-accessible')) {
                            $subtypeSelect.select2('destroy');
                        }
                        $subtypeSelect.select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Select Subtype',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: 0
                        });
                    }
                }, 100);
            }
        })
        .catch(error => {
            console.error('Error loading subtypes:', error);
            subtypeSelect.innerHTML = '<option value="">Error loading subtypes</option>';
        });
}

// Initialize clear buttons functionality  
function initializeClearButtons() {
    const clearCategoryBtn = document.getElementById('clear-category-btn');
    const clearEquipmentBtn = document.getElementById('clear-equipment-btn');
    
    // Clear Category Button
    if (clearCategoryBtn) {
        clearCategoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categorySelect = document.getElementById('category_id');
            const equipmentTypeSelect = document.getElementById('equipment_type_id');
            const subtypeSelect = document.getElementById('subtype_id');
            
            // Clear category selection
            categorySelect.value = '';
            
            // Clear equipment classification 
            equipmentTypeSelect.innerHTML = '<option value="">Select Item Type</option>';
            subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
            
            // Handle Select2
            if (window.jQuery) {
                if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#category_id').val('').trigger('change');
                }
                if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#equipment_type_id').val('').trigger('change');
                }
                if (window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#subtype_id').val('').trigger('change');
                }
            }
            
            // Hide name regeneration
            hideNameRegeneration();
            
            console.log('Category and equipment classification cleared in edit form');
        });
    }
    
    // Clear Equipment Button  
    if (clearEquipmentBtn) {
        clearEquipmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const equipmentTypeSelect = document.getElementById('equipment_type_id');
            const subtypeSelect = document.getElementById('subtype_id');
            
            // Clear selections
            equipmentTypeSelect.value = '';
            subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
            
            // Handle Select2
            if (window.jQuery) {
                if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#equipment_type_id').val('').trigger('change');
                }
                if (window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                    window.jQuery('#subtype_id').val('').trigger('change');
                }
            }
            
            // Hide name regeneration
            hideNameRegeneration();
            
            console.log('Equipment type and subtype cleared in edit form');
        });
    }
}

// Name regeneration functions
function tryRegenerateName() {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    const brandSelect = document.getElementById('brand');
    const modelInput = document.getElementById('model');
    
    if (!equipmentTypeSelect.value || !subtypeSelect.value) {
        hideNameRegeneration();
        return;
    }
    
    const brand = brandSelect ? brandSelect.value : '';
    const model = modelInput ? modelInput.value : '';
    
    console.log('Attempting to regenerate name for edit form');
    
    const params = new URLSearchParams({
        action: 'generate-name',
        equipment_type_id: equipmentTypeSelect.value,
        subtype_id: subtypeSelect.value,
        brand: brand,
        model: model
    });
    
    fetch(`?route=api/intelligent-naming&${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const regeneratedName = data.data.generated_name;
                const currentName = document.getElementById('name').value;
                
                // Only show regeneration if the new name is different
                if (regeneratedName && regeneratedName !== currentName) {
                    showNameRegeneration(regeneratedName);
                } else {
                    hideNameRegeneration();
                }
            } else {
                hideNameRegeneration();
            }
        })
        .catch(error => {
            console.error('Error regenerating name:', error);
            hideNameRegeneration();
        });
}

function showNameRegeneration(regeneratedName) {
    const nameRegeneration = document.getElementById('name-regeneration');
    const previewNameDiv = document.getElementById('preview-regenerated-name');
    
    if (nameRegeneration && previewNameDiv) {
        previewNameDiv.textContent = regeneratedName;
        nameRegeneration.style.display = 'block';
    }
}

function hideNameRegeneration() {
    const nameRegeneration = document.getElementById('name-regeneration');
    if (nameRegeneration) {
        nameRegeneration.style.display = 'none';
    }
}

// Intelligent unit auto-population (like creation forms)
function updateIntelligentUnit(equipmentTypeId, subtypeId = null) {
    console.log('Updating intelligent unit for edit form - item type:', equipmentTypeId, 'subtype:', subtypeId);
    
    const unitSelect = document.getElementById('unit');
    if (!unitSelect || !equipmentTypeId) return;
    
    const params = new URLSearchParams({
        action: 'intelligent-unit',
        equipment_type_id: equipmentTypeId
    });
    
    if (subtypeId) {
        params.append('subtype_id', subtypeId);
    }
    
    fetch(`?route=api/intelligent-naming&${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.unit) {
                const suggestedUnit = data.data.unit;
                console.log('Intelligent unit suggested:', suggestedUnit);
                
                // Check if the suggested unit exists in the dropdown
                const optionExists = Array.from(unitSelect.options).some(option => option.value === suggestedUnit);
                
                if (optionExists) {
                    // Only auto-select if unit is currently default or empty
                    if (!unitSelect.value || unitSelect.value === 'pcs') {
                        unitSelect.value = suggestedUnit;
                        console.log('Unit auto-selected in edit form:', suggestedUnit);
                        
                        // Show notification
                        showUnitAutoSelectionMessage(suggestedUnit);
                    }
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
        notification.style.minWidth = '300px';
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

// Initialize Select2 dropdowns (exactly like legacy and create forms)
function initializeSelect2() {
    // Wait for jQuery and Select2 to be available
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        setTimeout(initializeSelect2, 100);
        return;
    }
    
    console.log('Initializing Select2 for edit form dropdowns');
    
    // Store any pre-selected values
    var selectedProject = $('#project_id').val();
    var selectedCategory = $('#category_id').val();
    var selectedVendor = $('#vendor_id').val();
    var selectedMaker = $('#maker_id').val();
    var selectedClient = $('#client_id').val();
    var selectedBrand = $('#brand').val();
    var selectedUnit = $('#unit').val();
    
    // Category dropdown - preserve existing options
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

    // Project dropdown - preserve existing options
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

    // Item Type dropdown
    $('#equipment_type_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Item Type',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });

    // Subtype dropdown
    $('#subtype_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Subtype',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });

    // Brand dropdown
    $('#brand').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type to search brands...',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    
    // Restore selected value if any
    if (selectedBrand) {
        $('#brand').val(selectedBrand).trigger('change.select2');
    }
    
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

    // Unit dropdown
    $('#unit').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Unit',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    
    // Restore selected value if any
    if (selectedUnit) {
        $('#unit').val(selectedUnit).trigger('change.select2');
    }

    // Vendor dropdown
    $('#vendor_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type to search vendors...',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    
    // Restore selected value if any
    if (selectedVendor) {
        $('#vendor_id').val(selectedVendor).trigger('change.select2');
    }

    // Maker dropdown
    $('#maker_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type to search users...',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    
    // Restore selected value if any
    if (selectedMaker) {
        $('#maker_id').val(selectedMaker).trigger('change.select2');
    }

    // Client dropdown
    $('#client_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Type to search clients...',
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 0
    });
    
    // Restore selected value if any
    if (selectedClient) {
        $('#client_id').val(selectedClient).trigger('change.select2');
    }

    // Preserve existing event handlers by triggering native events
    $('#category_id').on('select2:select', function() {
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });
    
    $('#equipment_type_id').on('select2:select', function() {
        console.log('Select2 item type selected:', this.value);
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });
    
    $('#equipment_type_id').on('select2:clear', function() {
        console.log('Select2 item type cleared');
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Add Select2 clear event handlers (bidirectional clearing like creation forms)
    $('#category_id').on('select2:clear', function() {
        console.log('Category cleared via Select2 in edit form');
        // Clear item type when category is cleared
        $('#equipment_type_id').val('').trigger('change');
        $('#subtype_id').val('').trigger('change');
    });

    $('#equipment_type_id').on('select2:clear', function() {
        console.log('Equipment type cleared via Select2 in edit form');  
        $('#subtype_id').val('').trigger('change');
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

    console.log('Select2 initialized for edit form dropdowns with search functionality');
}

// Reinitialize Select2 for dynamically populated dropdowns
function reinitializeSelect2ForDynamicDropdowns() {
    console.log('Reinitializing Select2 for dynamic dropdowns...');
    
    // Reinitialize item type dropdown if it has options now
    const equipmentTypeSelect = $('#equipment_type_id');
    if (equipmentTypeSelect.length && equipmentTypeSelect.find('option').length > 1) {
        console.log('Reinitializing item type dropdown with', equipmentTypeSelect.find('option').length, 'options');
        equipmentTypeSelect.select2('destroy');
        equipmentTypeSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Item Type',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
        
        // Reattach event handlers
        equipmentTypeSelect.on('select2:select', function() {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        equipmentTypeSelect.on('select2:clear', function() {
            console.log('Equipment type cleared via Select2 in edit form');  
            $('#subtype_id').val('').trigger('change');
        });
    }
    
    // Reinitialize subtype dropdown if it has options now
    const subtypeSelect = $('#subtype_id');
    if (subtypeSelect.length && subtypeSelect.find('option').length > 1) {
        console.log('Reinitializing subtype dropdown with', subtypeSelect.find('option').length, 'options');
        subtypeSelect.select2('destroy');
        subtypeSelect.select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Subtype',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    }
    
    console.log('Dynamic dropdowns reinitialized');
}

</script>

<!-- Asset Standardizer JavaScript -->
<script src="/assets/js/asset-standardizer.js"></script>
<script>
// Asset edit form specific initialization
document.addEventListener('DOMContentLoaded', function() {
    // Pre-populate current values for comparison
    const currentName = document.getElementById('name').value;
    const currentStandardized = document.getElementById('standardized_name').value;
    
    // Show helpful info if asset already has standardized data
    if (currentStandardized && currentStandardized !== currentName) {
        const feedback = document.getElementById('name-feedback');
        if (feedback) {
            feedback.innerHTML = `Current standardized name: <strong class="text-success">${currentStandardized}</strong><br>
                                <small class="text-muted">Changes will update standardization</small>`;
        }
    }
    
    // Override validation to account for existing data
    if (window.assetStandardizer) {
        const originalProcessValidation = window.assetStandardizer.processNameValidation;
        window.assetStandardizer.processNameValidation = function(data) {
            // Call original processing
            originalProcessValidation.call(this, data);
            
            // Add edit-specific feedback
            if (data.standardized !== currentName) {
                const feedback = document.getElementById('name-feedback');
                if (feedback && data.confidence > 0.7) {
                    feedback.innerHTML += `<br><small class="text-info">
                        <i class="bi bi-arrow-right me-1"></i>Will be standardized to: <strong>${data.standardized}</strong>
                    </small>`;
                }
            }
        };
    }
    
    // Auto-validate current name on load
    if (currentName && window.assetStandardizer) {
        setTimeout(() => {
            window.assetStandardizer.validateAssetName(currentName);
        }, 500);
    }

    // Initialize disciplines
    initializeDisciplineHandling();
});

// Discipline handling
let allDisciplines = [];

function initializeDisciplineHandling() {
    loadAllDisciplines();
}

function loadAllDisciplines() {
    fetch('?route=api/assets/disciplines', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRFTokenValue
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.disciplines) {
            allDisciplines = data.disciplines;
            populateAllDisciplines();
        }
    })
    .catch(error => {
        console.error('Error loading disciplines:', error);
    });
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
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
