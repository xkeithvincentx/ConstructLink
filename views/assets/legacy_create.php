<?php
/**
 * Add Legacy Inventory Item View
 *
 * DATABASE MAPPING NOTE:
 * - This view displays "Legacy Item" / "Legacy Inventory Item" to users
 * - Backend uses AssetController and `assets` database table
 * - See controllers/AssetController.php header for full mapping documentation
 */

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
        <span class="d-none d-sm-inline">Back to Inventory</span>
        <span class="d-sm-none">Back</span>
    </a>
</div>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Legacy Item Entry
    </h6>
    <p class="mb-0">
        Use this form to quickly add existing inventory items that are already on the project site.
        Items will be pending verification by the Site Inventory Clerk before final authorization.
    </p>
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

<?php if (!in_array($user['role_name'], $roleConfig['assets/legacy-create'] ?? [])): ?>
    <div class="alert alert-danger mt-4">You do not have permission to create legacy inventory items.</div>
<?php else: ?>
<div class="row">
    <div class="col-lg-8 col-xl-9">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clipboard me-1"></i>Legacy Item Information
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=assets/legacy-create" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Basic Information
                            </h6>
                        </div>
                        
                        <!-- Intelligent Asset Classification -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="equipment_type_id" class="form-label">
                                    Item Type <span class="text-danger">*</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="clear-equipment-btn" title="Clear equipment selection and reset to all types">
                                        <i class="bi bi-arrow-clockwise"></i> Clear
                                    </button>
                                </label>
                                <select class="form-select" id="equipment_type_id" name="equipment_type_id" required>
                                    <option value="">Type to search equipment...</option>
                                    <!-- Populated with all item types for intelligent search -->
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-lightbulb text-warning"></i>
                                    Start typing equipment name (e.g., "drill", "hammer") - category will be auto-selected
                                </div>
                                <div class="invalid-feedback">
                                    Please select an item type.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subtype_id" class="form-label">Item Subtype <span class="text-danger" id="subtype-required-asterisk" style="display: none;">*</span></label>
                                <select class="form-select" id="subtype_id" name="subtype_id">
                                    <option value="">Select Subtype</option>
                                    <!-- Populated dynamically based on item type -->
                                </select>
                                <div class="invalid-feedback">
                                    Please select an item subtype.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Intelligent Item Name Generation -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="asset_name" class="form-label">Item Name</label>

                                <!-- Name Preview -->
                                <div id="name-preview" class="alert alert-success d-none" style="margin-bottom: 0.5rem;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="bi bi-lightbulb me-2"></i>
                                            <strong>Generated Name: </strong>
                                            <span id="preview-name">-</span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-success" id="use-generated-name">
                                            <i class="bi bi-check me-1"></i>Use This
                                        </button>
                                    </div>
                                </div>

                                <div class="input-group">
                                    <input type="text" class="form-control" id="asset_name" name="name"
                                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                                           placeholder="Name will be generated automatically or enter custom name"
                                           maxlength="200">
                                    <button type="button" class="btn btn-outline-secondary" id="manual-edit-toggle">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>

                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Item name is automatically generated from your selections above. Click the pencil to edit manually.
                                </div>
                                
                                <!-- Hidden fields for intelligent naming -->
                                <input type="hidden" id="generated_name" name="generated_name">
                                <input type="hidden" id="is_custom_name" name="is_custom_name" value="0">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Brief description of the asset..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
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
                                <select class="form-select" id="category_id" name="category_id" required data-disciplines="true">
                                    <option value="">Select Category</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" 
                                                    data-asset-type="<?= htmlspecialchars($category['asset_type'] ?? 'capital') ?>"
                                                    data-generates-assets="<?= $category['generates_assets'] ? '1' : '0' ?>"
                                                    data-is-consumable="<?= $category['is_consumable'] ? '1' : '0' ?>"
                                                    data-threshold="<?= htmlspecialchars($category['capitalization_threshold'] ?? '0') ?>"
                                                    data-business-desc="<?= htmlspecialchars($category['business_description'] ?? '') ?>"
                                                    data-disciplines="<?= htmlspecialchars($category['discipline_tags'] ?? '') ?>"
                                                    data-keywords="<?= htmlspecialchars($category['search_keywords'] ?? '') ?>"
                                                    <?= ($formData['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                                <?php 
                                                $assetTypeIcon = '';
                                                switch($category['asset_type'] ?? 'capital') {
                                                    case 'capital': $assetTypeIcon = '🔧'; break;
                                                    case 'inventory': $assetTypeIcon = '📦'; break;
                                                    case 'expense': $assetTypeIcon = '💰'; break;
                                                }
                                                echo $assetTypeIcon . ' ' . htmlspecialchars($category['name']);
                                                ?>
                                                <?= $category['is_consumable'] ? ' (Consumable)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a category.
                                </div>
                                
                                <!-- Category Business Information Panel -->
                                <div id="category-info" class="mt-2" style="display: none;">
                                    <div class="card border-info">
                                        <div class="card-body p-2">
                                            <h6 class="card-title text-info mb-1">
                                                <i class="bi bi-info-circle me-1"></i>Category Business Classification
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small>
                                                        <strong>Asset Type:</strong> <span id="category-asset-type"></span><br>
                                                        <strong>Generates Assets:</strong> <span id="category-generates-assets"></span>
                                                    </small>
                                                </div>
                                                <div class="col-md-6">
                                                    <small>
                                                        <strong>Consumable:</strong> <span id="category-is-consumable"></span><br>
                                                        <strong>Threshold:</strong> <span id="category-threshold"></span>
                                                    </small>
                                                </div>
                                            </div>
                                            <div id="category-business-desc" class="mt-2" style="display: none;">
                                                <small class="text-muted"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <?php if (in_array($userRole, ['System Admin', 'Asset Director'])): ?>
                                    <!-- Full project access for System Admin and Asset Director -->
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
                                <?php else: ?>
                                    <!-- Restricted access for other roles - current user's project only -->
                                    <?php 
                                    $userProjectId = $user['current_project_id'] ?? null;
                                    $userProject = null;
                                    if ($userProjectId && !empty($projects)) {
                                        foreach ($projects as $project) {
                                            if ($project['id'] == $userProjectId) {
                                                $userProject = $project;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($userProject): ?>
                                        <select class="form-select" id="project_id" name="project_id" required>
                                            <option value="<?= $userProject['id'] ?>" selected>
                                                <?= htmlspecialchars($userProject['name']) ?>
                                                <?php if (!empty($userProject['location'])): ?>
                                                    - <?= htmlspecialchars($userProject['location']) ?>
                                                <?php endif; ?>
                                            </option>
                                        </select>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Legacy assets are assigned to your current project: <strong><?= htmlspecialchars($userProject['name']) ?></strong>
                                        </div>
                                    <?php else: ?>
                                        <select class="form-select" id="project_id" name="project_id" required disabled>
                                            <option value="">No project assigned</option>
                                        </select>
                                        <div class="form-text text-warning">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            You are not assigned to a project. Please contact your System Administrator.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="invalid-feedback">
                                    Please select a project.
                                </div>
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
                                <label for="quantity" class="form-label">
                                    Quantity
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="bulk-entry-toggle" style="display: none;">
                                        <i class="bi bi-stack"></i> Bulk Entry
                                    </button>
                                </label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?= htmlspecialchars($formData['quantity'] ?? '1') ?>" 
                                       min="1" max="9999">
                                <div class="form-text" id="quantity-help">
                                    <span id="quantity-consumable-text" style="display: none;">
                                        <i class="bi bi-info-circle me-1 text-success"></i>
                                        Quantity for consumable items
                                    </span>
                                    <span id="quantity-serialized-text" style="display: none;">
                                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                                        Serial-tracked items: quantity = 1 (each item needs separate entry)
                                    </span>
                                    <span id="quantity-bulk-text" style="display: none;">
                                        <i class="bi bi-stack me-1 text-primary"></i>
                                        Bulk entry allowed - identical non-serialized items
                                    </span>
                                    <span id="quantity-default-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Item quantity
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bulk Entry Panel (Hidden by default) -->
                        <div class="col-12" id="bulk-entry-panel" style="display: none;">
                            <div class="alert alert-info border-primary">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><i class="bi bi-stack me-2"></i>Bulk Entry Mode</h6>
                                        <p class="mb-2">Creating multiple identical items efficiently. Each item will get a unique asset reference.</p>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Items to create:</label>
                                                <input type="number" class="form-control" id="bulk-quantity" min="2" max="999" value="10">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Serial numbering:</label>
                                                <select class="form-select" id="bulk-serial-mode">
                                                    <option value="none">No serial numbers</option>
                                                    <option value="sequence">Sequential (001, 002, 003...)</option>
                                                    <option value="custom">Custom prefix</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3" id="bulk-prefix-container" style="display: none;">
                                                <label class="form-label">Serial prefix:</label>
                                                <input type="text" class="form-control" id="bulk-serial-prefix" placeholder="e.g., HAM-">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulk-entry-close">
                                        <i class="bi bi-x"></i>
                                    </button>
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
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="specifications" class="form-label">Detailed Specifications</label>
                                <textarea class="form-control" id="specifications" name="specifications" rows="3"
                                          placeholder="Technical specifications, dimensions, capacity, etc..."><?= htmlspecialchars($formData['specifications'] ?? '') ?></textarea>
                                <div class="form-text">Optional technical details for legacy assets</div>
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
                                <label for="acquired_date" class="form-label">Estimated Acquired Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="acquired_date" name="acquired_date" 
                                       value="<?= htmlspecialchars($formData['acquired_date'] ?? date('Y-m-d')) ?>" 
                                       max="<?= date('Y-m-d') ?>" required>
                                <div class="form-text">Approximate date when asset was acquired (can be estimated)</div>
                                <div class="invalid-feedback">
                                    Please provide an acquired date.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                                       value="<?= htmlspecialchars($formData['warranty_expiry'] ?? '') ?>">
                                <div class="form-text">Manufacturer warranty expiration date</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="acquisition_cost" class="form-label">Acquisition Cost (Estimated)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="acquisition_cost" name="acquisition_cost" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['acquisition_cost'] ?? '') ?>"
                                           placeholder="Estimated cost">
                                </div>
                                <div class="form-text">Estimated cost when acquired (optional)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="unit_cost" name="unit_cost" 
                                           step="0.01" min="0" value="<?= htmlspecialchars($formData['unit_cost'] ?? '') ?>"
                                           placeholder="Cost per unit">
                                </div>
                                <div class="form-text">Individual unit cost if different from total</div>
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
                                       placeholder="Warehouse, Tool Room, Site Area, etc.">
                                <div class="form-text">Where is this asset currently located?</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="condition_notes" class="form-label">Condition Notes</label>
                                <textarea class="form-control" id="condition_notes" name="condition_notes" rows="3" 
                                          placeholder="Describe the current condition of the asset..."><?= htmlspecialchars($formData['condition_notes'] ?? '') ?></textarea>
                                <div class="form-text">Current condition and any notes about the asset</div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Brand & Engineering Usage Smart Section -->
                    <div class="row mb-4" id="brand-discipline-section">
                        <div class="col-12">
                            <h6 class="text-info border-bottom pb-2 mb-3">
                                <i class="bi bi-award me-1"></i>Brand & Engineering Usage
                                <small class="text-muted ms-2">Smart classification for legacy assets</small>
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
                                <div class="form-text">Select from verified construction brands (updates generated name)</div>
                                <!-- Hidden fields for brand standardization -->
                                <input type="hidden" id="standardized_brand" name="standardized_brand">
                                <input type="hidden" id="brand_id" name="brand_id">
                            </div>
                        </div>
                        
                        <!-- Model/Serial Section -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="model" class="form-label">Model</label>
                                        <input type="text" class="form-control" id="model" name="model" 
                                               value="<?= htmlspecialchars($formData['model'] ?? '') ?>" 
                                               placeholder="e.g., DHP484Z">
                                        <div class="form-text">Updates generated name</div>
                                    </div>
                                </div>
                                <div class="col-6">
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
                                    <strong>Multi-Disciplinary Classification:</strong> This legacy asset type is used across multiple engineering disciplines. Select the applicable ones.
                                </div>
                            </div>
                            
                            <div class="col-lg-4 col-md-12">
                                <div class="mb-3">
                                    <label for="primary_discipline" class="form-label">Primary Discipline</label>
                                    <select class="form-select" id="primary_discipline" name="primary_discipline">
                                        <option value="">Select Primary Use (Optional)</option>
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
                                    <div class="form-text">Select all applicable disciplines for this legacy asset</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Client Supplied -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_client_supplied" name="is_client_supplied" 
                                       <?= !empty($formData['is_client_supplied']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_client_supplied">
                                    <strong>Client Supplied Asset</strong>
                                </label>
                                <div class="form-text">Check if this asset was provided by the client</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                                <button type="button" class="btn btn-secondary" onclick="history.back()">
                                    <i class="bi bi-arrow-left me-1"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check me-1"></i>Add Legacy Item
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-xl-3">
        <!-- Help Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-question-circle me-1"></i>Quick Help
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-success">What is a Legacy Item?</h6>
                <p class="small">Legacy items are inventory that already exists on the project site before the ConstructLink system was implemented.</p>
                
                <h6 class="text-primary">Workflow Process:</h6>
                <ol class="small">
                    <li><strong>You (Warehouseman)</strong> - Add the asset</li>
                    <li><strong>Site Inventory Clerk</strong> - Verifies the asset exists</li>
                    <li><strong>Project Manager</strong> - Authorizes as project property</li>
                </ol>
                
                <h6 class="text-info">Tips for Quick Entry:</h6>
                <ul class="small">
                    <li>Use clear, descriptive asset names</li>
                    <li>Estimated dates are acceptable</li>
                    <li>Group similar items when possible</li>
                    <li>Add condition notes for maintenance planning</li>
                </ul>
            </div>
        </div>
        
        <!-- Recent Entries -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-clock me-1"></i>Today's Progress
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h4 class="text-success mb-1" id="todayCount">-</h4>
                    <small class="text-muted">Legacy assets added today</small>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="addAnother()">
                        <i class="bi bi-plus me-1"></i>Add Another Similar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Get CSRF token value for API calls
const CSRFTokenValue = document.querySelector('input[name="csrf_token"]')?.value || '';

// Enhanced form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-suggest dates based on user input
document.getElementById('acquired_date').addEventListener('change', function() {
    const selectedDate = new Date(this.value);
    const today = new Date();
    const diffTime = Math.abs(today - selectedDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 365) {
        const conditionSelect = document.getElementById('condition_notes');
        if (!conditionSelect.value) {
            conditionSelect.value = 'Fair - Some wear';
        }
    }
});

// Add another similar asset functionality
function addAnother() {
    const form = document.querySelector('form');
    const categorySelect = document.getElementById('category_id');
    const locationSelect = document.getElementById('sub_location');
    const conditionSelect = document.getElementById('condition_notes');
    
    // Store current values
    const category = categorySelect.value;
    const location = locationSelect.value;
    const condition = conditionSelect.value;
    
    // Reset form but keep some values
    form.reset();
    
    // Restore helpful values
    categorySelect.value = category;
    locationSelect.value = location;
    conditionSelect.value = condition;
    document.getElementById('acquired_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('quantity').value = '1';
    
    // Focus on name field
    document.getElementById('name').focus();
}

// Category selection handler for business classification
function updateCategoryInfo() {
    const categorySelect = document.getElementById('category_id');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryInfo = document.getElementById('category-info');
    
    if (categorySelect.value === '') {
        categoryInfo.style.display = 'none';
        return;
    }
    
    // Get category business data
    const assetType = selectedOption.getAttribute('data-asset-type') || 'capital';
    const generatesAssets = selectedOption.getAttribute('data-generates-assets') === '1';
    const isConsumable = selectedOption.getAttribute('data-is-consumable') === '1';
    const threshold = selectedOption.getAttribute('data-threshold') || '0';
    const businessDesc = selectedOption.getAttribute('data-business-desc') || '';
    
    // Update UI elements
    document.getElementById('category-asset-type').innerHTML = getAssetTypeDisplay(assetType);
    document.getElementById('category-generates-assets').innerHTML = generatesAssets ? 
        '<span class="text-success">Yes</span>' : '<span class="text-danger">No - Direct Expense</span>';
    document.getElementById('category-is-consumable').innerHTML = isConsumable ? 
        '<span class="text-info">Yes</span>' : '<span class="text-muted">No</span>';
    document.getElementById('category-threshold').innerHTML = threshold > 0 ? 
        '$' + parseFloat(threshold).toFixed(2) : '<span class="text-muted">No threshold</span>';
    
    // Show business description if available
    const businessDescDiv = document.getElementById('category-business-desc');
    if (businessDesc) {
        businessDescDiv.querySelector('small').textContent = businessDesc;
        businessDescDiv.style.display = 'block';
    } else {
        businessDescDiv.style.display = 'none';
    }
    
    // Show category info panel
    categoryInfo.style.display = 'block';
    
    // Show warning for expense-only categories
    if (!generatesAssets) {
        showCategoryWarning('This category is configured for direct expenses only. Legacy assets cannot be created for this category type.');
    } else {
        hideCategoryWarning();
    }
}

function getAssetTypeDisplay(assetType) {
    const types = {
        'capital': '<span class="badge bg-primary">🔧 Capital Asset</span>',
        'inventory': '<span class="badge bg-info">📦 Inventory/Materials</span>',
        'expense': '<span class="badge bg-warning">💰 Direct Expense</span>'
    };
    return types[assetType] || types['capital'];
}

function showCategoryWarning(message) {
    let warningDiv = document.getElementById('category-warning');
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'category-warning';
        warningDiv.className = 'alert alert-warning alert-dismissible fade show mt-2';
        warningDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span class="warning-text"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('category-info').appendChild(warningDiv);
    }
    warningDiv.querySelector('.warning-text').textContent = message;
    warningDiv.style.display = 'block';
}

function hideCategoryWarning() {
    const warningDiv = document.getElementById('category-warning');
    if (warningDiv) {
        warningDiv.style.display = 'none';
    }
}

// Function to update quantity field based on category
function updateQuantityField() {
    const categorySelect = document.getElementById('category_id');
    const quantityInput = document.getElementById('quantity');
    const quantityConsumableText = document.getElementById('quantity-consumable-text');
    const quantityNonConsumableText = document.getElementById('quantity-non-consumable-text');
    
    if (!categorySelect || !quantityInput) return;
    
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    
    if (categorySelect.value && selectedOption) {
        const isConsumable = selectedOption.getAttribute('data-is-consumable') === '1';
        
        if (isConsumable) {
            // Enable quantity input for consumables
            quantityInput.disabled = false;
            quantityInput.min = 1;
            if (quantityConsumableText) quantityConsumableText.style.display = 'inline';
            if (quantityNonConsumableText) quantityNonConsumableText.style.display = 'none';
        } else {
            // Disable quantity input for non-consumables, set to 1
            quantityInput.value = 1;
            quantityInput.disabled = true;
            if (quantityConsumableText) quantityConsumableText.style.display = 'none';
            if (quantityNonConsumableText) quantityNonConsumableText.style.display = 'inline';
        }
    } else {
        // No category selected, default behavior
        quantityInput.disabled = false;
        if (quantityConsumableText) quantityConsumableText.style.display = 'none';
        if (quantityNonConsumableText) quantityNonConsumableText.style.display = 'inline';
    }
}

// Initialize intelligent naming system
let currentGeneratedName = '';
let isManualEdit = false;
let allEquipmentTypes = []; // Store all item types for filtering
let equipmentTypeToCategory = {}; // Map item type ID to category data

function initializeIntelligentNaming() {
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    
    // Load all item types for intelligent search on page load
    loadAllEquipmentTypes();
    
    // Initialize clear buttons
    initializeClearButtons();
    const brandSelect = document.getElementById('brand');
    const modelInput = document.getElementById('model');
    const nameInput = document.getElementById('asset_name');
    const namePreview = document.getElementById('name-preview');
    const previewNameSpan = document.getElementById('preview-name');
    const useGeneratedButton = document.getElementById('use-generated-name');
    const manualEditToggle = document.getElementById('manual-edit-toggle');

    // Equipment type change handler with category auto-population
    if (equipmentTypeSelect) {
        equipmentTypeSelect.addEventListener('change', function() {
            const equipmentTypeId = this.value;
            console.log('Legacy form: Equipment type changed to:', equipmentTypeId);
            
            if (equipmentTypeId) {
                // Use the new API to get category info and auto-populate
                fetch(`?route=api/equipment-type-details&equipment_type_id=${equipmentTypeId}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Legacy form: API response:', data);
                        if (data.success && data.data && data.data.category_id) {
                            const currentCategoryId = categorySelect.value;
                            const targetCategoryId = data.data.category_id;
                            
                            console.log('Legacy form: Current category:', currentCategoryId, 'Target:', targetCategoryId);
                            
                            if (!currentCategoryId || currentCategoryId != targetCategoryId) {
                                console.log('Legacy form: Auto-selecting category:', targetCategoryId, data.data.category_name);
                                
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
                        console.error('Legacy form: Error loading item type details:', error);
                    });
                
                // Continue with existing functionality
                loadSubtypes(equipmentTypeId);
                clearNamePreview();
                updateQuantityHandling(equipmentTypeId);
                updateIntelligentUnit(equipmentTypeId);
            }
        });
    }

    // NOTE: Subtype change handling is now part of the UnifiedDropdownSync system

    // Brand/Model change - update name preview
    if (brandSelect) {
        brandSelect.addEventListener('change', function() {
            generateNamePreview();
        });
    }

    if (modelInput) {
        let modelTimeout;
        modelInput.addEventListener('input', function() {
            clearTimeout(modelTimeout);
            modelTimeout = setTimeout(generateNamePreview, 500);
        });
    }

    // Use generated name button
    if (useGeneratedButton) {
        useGeneratedButton.addEventListener('click', function() {
            nameInput.value = currentGeneratedName;
            document.getElementById('generated_name').value = currentGeneratedName;
            document.getElementById('is_custom_name').value = '0';
            namePreview.classList.add('d-none');
            isManualEdit = false;
        });
    }

    // Manual edit toggle
    if (manualEditToggle) {
        manualEditToggle.addEventListener('click', function() {
            isManualEdit = !isManualEdit;
            
            if (isManualEdit) {
                this.innerHTML = '<i class="bi bi-robot"></i>';
                this.title = 'Switch to auto-generated name';
                nameInput.placeholder = 'Enter custom asset name';
                document.getElementById('is_custom_name').value = '1';
            } else {
                this.innerHTML = '<i class="bi bi-pencil"></i>';
                this.title = 'Edit name manually';
                nameInput.placeholder = 'Name will be generated automatically or enter custom name';
                if (currentGeneratedName) {
                    nameInput.value = currentGeneratedName;
                    document.getElementById('generated_name').value = currentGeneratedName;
                }
                document.getElementById('is_custom_name').value = '0';
            }
        });
    }

    // Name input manual changes
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (isManualEdit) {
                document.getElementById('is_custom_name').value = '1';
            }
        });
    }
}


window.loadSubtypes = function(equipmentTypeId) {
    const subtypeSelect = document.getElementById('subtype_id');
    
    if (!equipmentTypeId) {
        subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
        // No item type selected - make subtype optional
        subtypeSelect.removeAttribute('required');
        const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
        if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
        return;
    }

    subtypeSelect.innerHTML = '<option value="">Loading...</option>';
    
    console.log('🔧 Loading subtypes for item type:', equipmentTypeId);
    const apiUrl = `?route=api/intelligent-naming&action=subtypes&equipment_type_id=${equipmentTypeId}`;
    console.log('🔧 API URL:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('🔧 Subtypes API response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔧 Subtypes API data:', data);
            subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
            
            if (data.success && data.data) {
                // Handle dynamic requirement based on subtype availability
                const subtypeAsterisk = document.getElementById('subtype-required-asterisk');
                
                if (data.data.length === 0) {
                    // No subtypes available - make field optional
                    subtypeSelect.innerHTML = '<option value="">No subtypes available</option>';
                    subtypeSelect.removeAttribute('required');
                    if (subtypeAsterisk) subtypeAsterisk.style.display = 'none';
                    console.log('🔧 No subtypes found - field is now optional');
                    return;
                } else {
                    // Subtypes available - make field required
                    subtypeSelect.setAttribute('required', 'required');
                    if (subtypeAsterisk) subtypeAsterisk.style.display = 'inline';
                    console.log('🔧 Subtypes found - field is now required');
                }
                
                data.data.forEach(subtype => {
                    const option = document.createElement('option');
                    option.value = subtype.id;
                    option.textContent = subtype.subtype_name;
                    
                    // Add additional info to title
                    const details = [];
                    if (subtype.power_source) details.push(`Power: ${subtype.power_source}`);
                    if (subtype.material_type) details.push(`Material: ${subtype.material_type}`);
                    if (subtype.size_category) details.push(`Size: ${subtype.size_category}`);
                    
                    if (details.length > 0) {
                        option.title = details.join(', ');
                    }
                    
                    subtypeSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading subtypes:', error);
            subtypeSelect.innerHTML = '<option value="">Error loading subtypes</option>';
        });
}

window.generateNamePreview = function() {
    const equipmentTypeId = document.getElementById('equipment_type_id').value;
    const subtypeId = document.getElementById('subtype_id').value;
    const brand = document.getElementById('brand').value;
    const model = document.getElementById('model').value;
    const namePreview = document.getElementById('name-preview');
    const previewNameDiv = document.getElementById('preview-name');

    if (!equipmentTypeId || !subtypeId) {
        clearNamePreview();
        return;
    }

    const params = new URLSearchParams({
        action: 'generate-name',
        equipment_type_id: equipmentTypeId,
        subtype_id: subtypeId,
        brand: brand || '',
        model: model || ''
    });

    fetch(`?route=api/intelligent-naming&${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                currentGeneratedName = data.data.generated_name;
                previewNameDiv.textContent = currentGeneratedName;
                namePreview.classList.remove('d-none');
                
                // Auto-populate if not manually editing
                if (!isManualEdit) {
                    const nameInput = document.getElementById('asset_name');
                    nameInput.value = currentGeneratedName;
                    document.getElementById('generated_name').value = currentGeneratedName;
                }
            }
        })
        .catch(error => {
            console.error('Error generating name:', error);
            clearNamePreview();
        });
}

function clearSubtypes() {
    const subtypeSelect = document.getElementById('subtype_id');
    subtypeSelect.innerHTML = '<option value="">Select Subtype</option>';
}

function clearNamePreview() {
    const namePreview = document.getElementById('name-preview');
    namePreview.classList.add('d-none');
    currentGeneratedName = '';
}

// Initialize Clear/Reset Buttons
function initializeClearButtons() {
    console.log('Initializing clear buttons...');
    const clearCategoryBtn = document.getElementById('clear-category-btn');
    const clearEquipmentBtn = document.getElementById('clear-equipment-btn');
    
    console.log('Clear category button found:', !!clearCategoryBtn);
    console.log('Clear equipment button found:', !!clearEquipmentBtn);
    
    if (clearCategoryBtn) {
        console.log('Clear category button element:', clearCategoryBtn);
        console.log('Clear category button visible:', clearCategoryBtn.offsetParent !== null);
        console.log('Clear category button disabled:', clearCategoryBtn.disabled);
        
        // Remove existing event listeners to prevent duplicates
        if (clearCategoryBtn._clearInitialized) {
            console.log('Clear category button already initialized, skipping...');
            return;
        }
    }
    
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    const subtypeSelect = document.getElementById('subtype_id');
    
    // Clear Category Button - Full Reset
    if (clearCategoryBtn) {
        console.log('Attaching click event to clear category button');
        
        // Add multiple event listeners to ensure button works
        clearCategoryBtn.addEventListener('click', function(e) {
            console.log('CLEAR CATEGORY BUTTON CLICKED - using unified system');
            
            // Prevent any default behavior
            e.preventDefault();
            e.stopPropagation();
            
            // Use the unified clear system
            if (window.UnifiedDropdownSync) {
                window.UnifiedDropdownSync.clearAllFields();
            } else {
                // Fallback to manual clearing if unified system not available
                console.warn('UnifiedDropdownSync not available, using manual clearing');
                categorySelect.value = '';
                equipmentTypeSelect.value = '';
                clearSubtypes();
            }
            
            // Clear name preview
            clearNamePreview();
            
            // Reset unit to default
            const unitSelect = document.getElementById('unit');
            if (unitSelect) unitSelect.value = 'pcs';
            
            // Show reset notification
            showResetNotification('All selections cleared');
        });
        
        // Test if button can be triggered programmatically
        clearCategoryBtn.addEventListener('mousedown', function(e) {
            console.log('Clear category button mousedown detected');
        });
        
        // Add test to verify button works
        console.log('Clear category button test click capability');
        setTimeout(() => {
            if (clearCategoryBtn && clearCategoryBtn.click) {
                console.log('Clear category button click method available');
            }
        }, 1000);
        
        // Mark as initialized
        clearCategoryBtn._clearInitialized = true;
    }
    
    // Clear Item Type Button - Bidirectional Reset (Clear both equipment and category)
    if (clearEquipmentBtn) {
        // Check if already initialized
        if (clearEquipmentBtn._clearInitialized) {
            console.log('Clear equipment button already initialized, skipping...');
            return;
        }
        
        console.log('Attaching click event to clear equipment button');
        clearEquipmentBtn.addEventListener('click', function() {
            console.log('CLEAR EQUIPMENT BUTTON CLICKED - bidirectional reset');
            
            // Prevent auto-category selection during this operation
            window.preventCategoryAutoSelection = true;
            
            // Clear item type selection
            equipmentTypeSelect.value = '';
            
            // Clear category selection (bidirectional)
            categorySelect.value = '';
            
            // Clear subtype
            clearSubtypes();
            
            // Clear name preview
            clearNamePreview();
            
            // Reset unit to default
            const unitSelect = document.getElementById('unit');
            if (unitSelect) unitSelect.value = 'pcs';
            
            // Show all item types (no category filter)
            if (allEquipmentTypes && allEquipmentTypes.length > 0) {
                populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
            } else {
                console.warn('allEquipmentTypes is empty, reloading...');
                loadAllEquipmentTypes();
            }
            
            // Trigger change events for both selects
            equipmentTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Also trigger Select2 events if available
            if (window.jQuery && window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#equipment_type_id').val('').trigger('change');
            }
            if (window.jQuery && window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#category_id').val('').trigger('change');
            }
            
            // Clear the prevention flag after events
            setTimeout(() => {
                window.preventCategoryAutoSelection = false;
            }, 200);
            
            // Show reset notification
            showResetNotification('Equipment and category cleared - showing all item types');
        });
        
        // Mark as initialized
        clearEquipmentBtn._clearInitialized = true;
    }
}

// Global function to manually trigger category reset (for debugging)
window.testCategoryReset = function() {
    console.log('Manual category reset triggered');
    const categorySelect = document.getElementById('category_id');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    
    // Clear both fields
    categorySelect.value = '';
    equipmentTypeSelect.value = '';
    
    // Handle Select2
    if (window.jQuery) {
        if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#category_id').val('').trigger('change');
        }
        if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
            window.jQuery('#equipment_type_id').val('').trigger('change');
        }
    }
    
    // Clear other fields
    clearSubtypes();
    clearNamePreview();
    
    // Reset unit
    const unitSelect = document.getElementById('unit');
    if (unitSelect) unitSelect.value = 'pcs';
    
    // Reload item types
    if (allEquipmentTypes && allEquipmentTypes.length > 0) {
        populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
    } else {
        loadAllEquipmentTypes();
    }
    
    showResetNotification('Manual reset completed - all fields cleared');
};

// Global function to test quick entry (for debugging)
window.testQuickEntry = function(equipmentName) {
    console.log('Testing quick entry with:', equipmentName || 'Hand Tool');
    quickFillEquipment(equipmentName || 'Hand Tool');
};

// Simplified event delegation for clear buttons (more reliable)
document.addEventListener('click', function(e) {
    // Handle clear category button
    if (e.target && e.target.id === 'clear-category-btn') {
        console.log('Clear category button clicked via delegation');
        e.preventDefault();
        e.stopPropagation();
        
        const categorySelect = document.getElementById('category_id');
        const equipmentTypeSelect = document.getElementById('equipment_type_id');
        
        // Clear both fields
        categorySelect.value = '';
        equipmentTypeSelect.value = '';
        
        // Handle Select2
        if (window.jQuery) {
            if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#category_id').val('').trigger('change');
            }
            if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#equipment_type_id').val('').trigger('change');
            }
        }
        
        // Clear other fields
        clearSubtypes();
        clearNamePreview();
        
        // Reset unit
        const unitSelect = document.getElementById('unit');
        if (unitSelect) unitSelect.value = 'pcs';
        
        // Reload item types
        if (allEquipmentTypes && allEquipmentTypes.length > 0) {
            populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
        } else {
            loadAllEquipmentTypes();
        }
        
        showResetNotification('All selections cleared - showing all item types');
        return false;
    }
    
    // Handle clear equipment button
    if (e.target && e.target.id === 'clear-equipment-btn') {
        console.log('Clear equipment button clicked via delegation');
        e.preventDefault();
        e.stopPropagation();
        
        // Set flag to prevent auto-category selection
        window.preventCategoryAutoSelection = true;
        
        const categorySelect = document.getElementById('category_id');
        const equipmentTypeSelect = document.getElementById('equipment_type_id');
        
        // Clear both fields (bidirectional clearing)
        categorySelect.value = '';
        equipmentTypeSelect.value = '';
        
        // Handle Select2
        if (window.jQuery) {
            if (window.jQuery('#category_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#category_id').val('').trigger('change');
            }
            if (window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                window.jQuery('#equipment_type_id').val('').trigger('change');
            }
        }
        
        // Clear other fields
        clearSubtypes();
        clearNamePreview();
        
        // Reset unit
        const unitSelect = document.getElementById('unit');
        if (unitSelect) unitSelect.value = 'pcs';
        
        // Reload item types
        if (allEquipmentTypes && allEquipmentTypes.length > 0) {
            populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
        }
        
        // Reset flag after processing
        setTimeout(() => {
            window.preventCategoryAutoSelection = false;
        }, 200);
        
        showResetNotification('Equipment and category cleared - showing all item types');
        return false;
    }
    
    // Handle quick entry buttons
    if (e.target && e.target.classList.contains('quick-fill')) {
        console.log('Quick fill button clicked via delegation');
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

// Show reset notification
function showResetNotification(message) {
    // Create or update notification
    let notification = document.getElementById('reset-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'reset-notification';
        notification.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        notification.style.top = '20px';
        notification.style.left = '50%';
        notification.style.transform = 'translateX(-50%)';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '350px';
        notification.style.textAlign = 'center';
        document.body.appendChild(notification);
    }
    
    notification.innerHTML = `
        <i class="bi bi-arrow-clockwise me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Auto-hide after 4 seconds
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, 4000);
}

// Load all item types for intelligent search
window.loadAllEquipmentTypes = function() {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    
    fetch(`?route=api/intelligent-naming&action=all-equipment-types`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                // Store all item types for filtering
                allEquipmentTypes = data.data;
                console.log('Loaded item types:', allEquipmentTypes.length);
                
                // Build item type to category mapping
                data.data.forEach(type => {
                    equipmentTypeToCategory[type.id] = {
                        categoryId: type.category_id,
                        categoryName: type.category_name
                    };
                });
                console.log('Built item type to category mapping:', equipmentTypeToCategory);
                
                // Initially show all item types
                populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
            }
        })
        .catch(error => {
            console.error('Error loading all item types:', error);
        });
}

// Populate item type dropdown with given types
function populateEquipmentTypeDropdown(types, placeholder = 'Select Item Type') {
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    equipmentTypeSelect.innerHTML = `<option value="">${placeholder}</option>`;
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.name + (placeholder.includes('search') ? ` (${type.category_name})` : '');
        option.dataset.categoryId = type.category_id;
        option.dataset.categoryName = type.category_name;
        if (type.description) {
            option.title = type.description;
        }
        equipmentTypeSelect.appendChild(option);
    });
    
    console.log('Populated dropdown with', types.length, 'item types');
}

// Filter item types by category
window.filterEquipmentTypesByCategory = function(categoryId) {
    if (!categoryId) {
        // Show all item types when no category selected
        populateEquipmentTypeDropdown(allEquipmentTypes, 'Type to search equipment...');
        return;
    }
    
    // Filter item types for the selected category
    const filteredTypes = allEquipmentTypes.filter(type => type.category_id == categoryId);
    console.log('Filtering by category', categoryId, '- found', filteredTypes.length, 'types');
    populateEquipmentTypeDropdown(filteredTypes, 'Select Item Type');
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

// Intelligent Unit Auto-Population
window.updateIntelligentUnit = function(equipmentTypeId, subtypeId = null) {
    console.log('Updating intelligent unit for item type:', equipmentTypeId, 'subtype:', subtypeId);
    
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

// Update quantity handling based on item type
function updateQuantityHandling(equipmentTypeId) {
    const quantityInput = document.getElementById('quantity');
    const quantityHelp = document.getElementById('quantity-help');
    const equipmentTypeSelect = document.getElementById('equipment_type_id');
    
    if (!equipmentTypeId || !quantityInput) return;
    
    const selectedOption = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex];
    const equipmentTypeName = selectedOption.textContent.split(' (')[0]; // Remove category part
    
    // Hide all help texts
    const helpTexts = ['quantity-consumable-text', 'quantity-serialized-text', 'quantity-bulk-text', 'quantity-default-text'];
    helpTexts.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.style.display = 'none';
    });
    
    // Define item types that allow bulk quantities (non-serialized, identical items)
    const bulkAllowedTypes = [
        'Hammer', 'Screwdriver', 'Wrench', 'Pliers', 'Hand Saw', 'Measuring Tool', 'Trowel'
    ];
    
    // Define item types that require serial tracking (expensive, unique items)
    const serializedTypes = [
        'Drill', 'Grinder', 'Saw', 'Sander', 'Router', 'Impact Tool', 'Nail Gun',
        'Arc Welder', 'Gas Welder', 'Plasma Cutter', 'Spot Welder', 'Multi-Process Welder'
    ];
    
    if (bulkAllowedTypes.includes(equipmentTypeName)) {
        // Allow bulk quantities for hand tools
        quantityInput.disabled = false;
        quantityInput.max = 999;
        quantityInput.style.backgroundColor = '';
        document.getElementById('quantity-bulk-text').style.display = 'inline';
        
        // Show bulk entry button
        const bulkToggle = document.getElementById('bulk-entry-toggle');
        if (bulkToggle) bulkToggle.style.display = 'inline-block';
        
        // Add special styling
        quantityInput.classList.add('border-primary');
        quantityInput.classList.remove('border-warning');
        
    } else if (serializedTypes.includes(equipmentTypeName)) {
        // Restrict to quantity 1 for serialized items
        quantityInput.value = 1;
        quantityInput.disabled = true;
        quantityInput.style.backgroundColor = '#f8f9fa';
        document.getElementById('quantity-serialized-text').style.display = 'inline';
        
        // Hide bulk entry button for serialized items
        const bulkToggle = document.getElementById('bulk-entry-toggle');
        if (bulkToggle) bulkToggle.style.display = 'none';
        
        // Add warning styling
        quantityInput.classList.add('border-warning');
        quantityInput.classList.remove('border-primary');
        
    } else {
        // Default behavior for other types
        quantityInput.disabled = false;
        quantityInput.max = 99;
        quantityInput.style.backgroundColor = '';
        document.getElementById('quantity-default-text').style.display = 'inline';
        
        // Hide bulk entry button
        const bulkToggle = document.getElementById('bulk-entry-toggle');
        if (bulkToggle) bulkToggle.style.display = 'none';
        
        quantityInput.classList.remove('border-primary', 'border-warning');
    }
    
    // Always hide bulk panel when item type changes
    const bulkPanel = document.getElementById('bulk-entry-panel');
    if (bulkPanel) {
        bulkPanel.style.display = 'none';
        const bulkToggle = document.getElementById('bulk-entry-toggle');
        if (bulkToggle) {
            bulkToggle.innerHTML = '<i class="bi bi-stack"></i> Bulk Entry';
            bulkToggle.classList.remove('btn-primary');
            bulkToggle.classList.add('btn-outline-primary');
        }
    }
}

// Load today's count and initialize category handler
document.addEventListener('DOMContentLoaded', function() {
    // This would be an AJAX call to get today's count
    document.getElementById('todayCount').textContent = '0';
    
    // Add category change event listener
    document.getElementById('category_id').addEventListener('change', function() {
        updateCategoryInfo();
        updateQuantityField();
    });
    
    // Initialize category info if category is already selected
    if (document.getElementById('category_id').value) {
        updateCategoryInfo();
        updateQuantityField();
    }
    
    // Initialize intelligent naming system
    initializeIntelligentNaming();
    
    // Initialize bulk entry functionality
    initializeBulkEntry();
    
    // Initialize quick entry shortcuts
    initializeQuickEntry();
});

// Initialize bulk entry functionality
function initializeBulkEntry() {
    const bulkToggle = document.getElementById('bulk-entry-toggle');
    const bulkPanel = document.getElementById('bulk-entry-panel');
    const bulkClose = document.getElementById('bulk-entry-close');
    const bulkSerialMode = document.getElementById('bulk-serial-mode');
    const bulkPrefixContainer = document.getElementById('bulk-prefix-container');
    const quantityInput = document.getElementById('quantity');
    const bulkQuantityInput = document.getElementById('bulk-quantity');
    
    // Show/hide bulk entry panel
    if (bulkToggle) {
        bulkToggle.addEventListener('click', function() {
            if (bulkPanel.style.display === 'none') {
                bulkPanel.style.display = 'block';
                this.innerHTML = '<i class="bi bi-x"></i> Close Bulk';
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
                
                // Sync bulk quantity with main quantity
                if (quantityInput.value > 1) {
                    bulkQuantityInput.value = quantityInput.value;
                }
            } else {
                bulkPanel.style.display = 'none';
                this.innerHTML = '<i class="bi bi-stack"></i> Bulk Entry';
                this.classList.remove('btn-primary');
                this.classList.add('btn-outline-primary');
            }
        });
    }
    
    // Close bulk entry panel
    if (bulkClose) {
        bulkClose.addEventListener('click', function() {
            bulkPanel.style.display = 'none';
            bulkToggle.innerHTML = '<i class="bi bi-stack"></i> Bulk Entry';
            bulkToggle.classList.remove('btn-primary');
            bulkToggle.classList.add('btn-outline-primary');
        });
    }
    
    // Handle serial numbering mode change
    if (bulkSerialMode) {
        bulkSerialMode.addEventListener('change', function() {
            if (this.value === 'custom') {
                bulkPrefixContainer.style.display = 'block';
            } else {
                bulkPrefixContainer.style.display = 'none';
            }
        });
    }
    
    // Sync bulk quantity with main quantity input
    if (bulkQuantityInput && quantityInput) {
        bulkQuantityInput.addEventListener('input', function() {
            if (parseInt(this.value) > 1) {
                quantityInput.value = this.value;
            }
        });
    }
}

// Initialize quick entry shortcuts for common warehouse items
function initializeQuickEntry() {
    // Add quick entry buttons after page load
    const alertDiv = document.querySelector('.alert.alert-info');
    console.log('Initializing quick entry - Alert div found:', !!alertDiv);
    
    if (alertDiv) {
        const quickEntryHtml = `
        <div class="card bg-light border-primary mb-4 mt-3">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Warehouse Quick Entry
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
        
        alertDiv.insertAdjacentHTML('afterend', quickEntryHtml);
        
        // Add event listeners to quick fill buttons
        document.querySelectorAll('.quick-fill').forEach(button => {
            button.addEventListener('click', function() {
                const equipmentName = this.dataset.equipment;
                console.log('Quick fill button clicked:', equipmentName);
                quickFillEquipment(equipmentName);
            });
        });
        
        console.log('Quick entry buttons added and event listeners attached');
    } else {
        console.warn('Alert div not found - quick entry buttons not added');
    }
}

// Quick fill item type
function quickFillEquipment(equipmentName) {
    console.log('Quick fill equipment called with:', equipmentName);
    const equipmentSelect = document.getElementById('equipment_type_id');
    
    if (!equipmentSelect) {
        console.error('Equipment select not found');
        return;
    }
    
    console.log('Equipment select found, options count:', equipmentSelect.options.length);
    
    // Find the item type option
    let found = false;
    for (let option of equipmentSelect.options) {
        console.log('Checking option:', option.textContent, 'against', equipmentName);
        if (option.textContent.toLowerCase().includes(equipmentName.toLowerCase())) {
            console.log('Match found! Setting value:', option.value);
            equipmentSelect.value = option.value;
            equipmentSelect.dispatchEvent(new Event('change'));
            
            // Trigger Select2 if initialized
            if (window.jQuery && window.jQuery('#equipment_type_id').hasClass('select2-hidden-accessible')) {
                console.log('Triggering Select2 change');
                window.jQuery('#equipment_type_id').val(option.value).trigger('change');
            }
            
            // Wait for category auto-selection and subtype loading, then try to generate name
            setTimeout(() => {
                const subtypeSelect = document.getElementById('subtype_id');
                if (subtypeSelect && subtypeSelect.options.length > 1) {
                    // Auto-select first available subtype for name generation
                    const firstSubtype = subtypeSelect.options[1]; // Skip empty option
                    if (firstSubtype) {
                        console.log('Auto-selecting first subtype for name generation:', firstSubtype.textContent);
                        subtypeSelect.value = firstSubtype.value;
                        subtypeSelect.dispatchEvent(new Event('change'));
                        
                        // Trigger Select2 if initialized
                        if (window.jQuery && window.jQuery('#subtype_id').hasClass('select2-hidden-accessible')) {
                            window.jQuery('#subtype_id').val(firstSubtype.value).trigger('change');
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
        console.warn(`No item type found matching: ${equipmentName}`);
        console.log('Available options:');
        for (let option of equipmentSelect.options) {
            if (option.value) console.log(' - ', option.textContent);
        }
    }
}
</script>

<!-- Asset Standardizer JavaScript -->
<script src="/assets/js/asset-standardizer.js"></script>
<script>
// Legacy asset form specific initialization
document.addEventListener('DOMContentLoaded', function() {
    // Show learning section for legacy assets (they're more likely to need corrections)
    const learningSection = document.getElementById('learning-section');
    if (learningSection) {
        learningSection.style.display = 'block';
    }
    
    // Special handling for legacy assets - be more permissive with unknown assets
    if (window.assetStandardizer) {
        const originalValidateAssetName = window.assetStandardizer.validateAssetName;
        window.assetStandardizer.validateAssetName = function(value) {
            // Call original validation
            originalValidateAssetName.call(this, value);
            
            // For legacy assets, show a helpful message for unknown assets
            setTimeout(() => {
                if (this.validationResults.name && this.validationResults.name.confidence < 0.3) {
                    const feedback = document.getElementById('name-feedback');
                    if (feedback) {
                        feedback.textContent = 'Legacy asset - system will learn from this entry';
                        feedback.className = 'form-text text-info';
                    }
                }
            }, 100);
        };
    }
    
    // Initialize smart discipline handling
    initializeDisciplineHandling();
    
    // Initialize brand validation 
    initializeBrandValidation();
});

// Smart Discipline handling for Legacy Assets
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
        // Show disciplines section by default for legacy assets
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
    console.log('Starting legacy disciplines API call...');
    fetch('?route=api/assets/disciplines&action=list', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRFTokenValue
        }
    })
    .then(response => {
        console.log('Legacy API response status:', response.status);
        return response.text(); // Get raw response first
    })
    .then(text => {
        console.log('Legacy raw API response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                allDisciplines = data.data;
                console.log('✓ Legacy disciplines loaded successfully:', allDisciplines.length, 'disciplines');
                
                // Try to populate immediately
                populateAllDisciplines();
            } else {
                console.error('❌ Legacy disciplines API error:', data.message);
            }
        } catch (parseError) {
            console.error('❌ Legacy JSON parse error:', parseError);
            console.error('Response was not valid JSON:', text);
        }
    })
    .catch(error => {
        console.error('❌ Network error loading legacy disciplines:', error);
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
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use (Optional)</option>';
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
    primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use (Optional)</option>';

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
        primaryDisciplineSelect.innerHTML = '<option value="">Select Primary Use (Optional)</option>';
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

// Brand validation functions for Legacy Assets
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
                context: 'Legacy Asset Creation Form'
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
        brandFeedback.textContent = 'Start typing for brand validation';
        brandFeedback.className = 'form-text';
    }
    
    // Clear hidden fields
    document.getElementById('standardized_brand').value = '';
    document.getElementById('brand_id').value = '';
}

// Form enhancements for legacy assets
document.addEventListener('DOMContentLoaded', function() {
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Debug: Check name field value before submission
            const nameField = document.getElementById('asset_name');
            if (nameField) {
                console.log('Legacy form submission - name field value:', nameField.value);
                
                // Fallback: If name field is empty but we have a generated name, use it
                if (!nameField.value.trim() && currentGeneratedName) {
                    console.log('Using generated name as fallback (legacy):', currentGeneratedName);
                    nameField.value = currentGeneratedName;
                }
                
                // Last resort fallback: Generate simple name from available data
                if (!nameField.value.trim()) {
                    const equipmentTypeSelect = document.getElementById('equipment_type_id');
                    const categorySelect = document.getElementById('category_id');
                    
                    if (equipmentTypeSelect && equipmentTypeSelect.value) {
                        const equipmentText = equipmentTypeSelect.options[equipmentTypeSelect.selectedIndex].textContent;
                        const fallbackName = equipmentText + ' - Legacy Asset';
                        console.log('Last resort fallback name:', fallbackName);
                        nameField.value = fallbackName;
                    } else if (categorySelect && categorySelect.value) {
                        const categoryText = categorySelect.options[categorySelect.selectedIndex].textContent;
                        const fallbackName = categoryText + ' - Legacy Asset';
                        console.log('Category-based fallback name:', fallbackName);
                        nameField.value = fallbackName;
                    }
                }
                
                // Additional check: If equipment is selected but no name, warn user
                const equipmentTypeSelect = document.getElementById('equipment_type_id');
                const subtypeSelect = document.getElementById('subtype_id');
                if (equipmentTypeSelect && subtypeSelect && equipmentTypeSelect.value && subtypeSelect.value && !nameField.value.trim()) {
                    console.warn('Legacy form: Equipment selected but name still empty - may need to wait for name generation');
                }
            }
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Scroll to first invalid field
                const firstInvalidField = form.querySelector(':invalid');
                if (firstInvalidField) {
                    firstInvalidField.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstInvalidField.focus();
                }
            }
            form.classList.add('was-validated');
        }, false);
    }
});
</script>

<style>
/* Responsive form styles for legacy create */
.card-body {
    padding: 1.25rem;
}

@media (max-width: 992px) {
    .card-body {
        padding: 1rem;
    }
    
    .alert {
        padding: 0.75rem;
        font-size: 0.9rem;
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
    
    /* Category info panel responsive */
    #category-info .card-body {
        padding: 0.75rem;
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
    
    /* Stack buttons vertically */
    .d-flex.justify-content-between,
    .d-flex.flex-column.flex-sm-row {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .d-flex.justify-content-between .btn,
    .d-flex.flex-column.flex-sm-row .btn {
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
    color: #495057;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: calc(2.5rem);
}

.select2-container--bootstrap-5 .select2-dropdown {
    border-color: #dee2e6;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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

/* Ensure proper display in form groups */
.form-group .select2-container {
    display: block;
}

/* Responsive adjustments for mobile */
@media (max-width: 576px) {
    .select2-container {
        width: 100% !important;
    }
    
    .select2-dropdown {
        width: 100% !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single {
        font-size: 16px; /* Prevent zoom on iOS */
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

/* Validation styling */
.was-validated .form-select:invalid ~ .select2-container .select2-selection {
    border-color: #dc3545;
}

.was-validated .form-select:valid ~ .select2-container .select2-selection {
    border-color: #198754;
}
</style>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Unified Dropdown Synchronization System -->
<script src="unified_dropdown_sync.js"></script>

<script>
// Wait for jQuery and Select2 to load
(function initSelect2() {
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        setTimeout(initSelect2, 100);
        return;
    }
    
    $(document).ready(function() {
        // Initialize Select2 for all dropdowns
        
        // Store any pre-selected values
        var selectedProject = $('#project_id').val();
        var selectedCategory = $('#category_id').val();
        
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
        
        // Add Select2 event handler for item type to trigger native change events
        $('#equipment_type_id').on('select2:select', function() {
            console.log('Select2 item type selected:', this.value);
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        $('#equipment_type_id').on('select2:clear', function() {
            console.log('Select2 item type cleared');
            this.dispatchEvent(new Event('change', { bubbles: true }));
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
    
        // Maker dropdown
        $('#maker_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Type to search users...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    
        // Status dropdown
        $('#status').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select status...',
            minimumResultsForSearch: 0,
            width: '100%'
        });
    
        // Condition dropdown
        $('#condition').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select condition...',
            minimumResultsForSearch: 0,
            width: '100%'
        });
    
    // Preserve existing event handlers by triggering native events
    $('#category_id').on('select2:select', function() {
        this.dispatchEvent(new Event('change', { bubbles: true }));
        
        // Also directly trigger intelligent naming
        const categoryId = this.value;
        if (window.filterEquipmentTypesByCategory) {
            window.filterEquipmentTypesByCategory(categoryId);
        }
    });
    
    // Handle dynamic discipline loading
    $('#category_id').on('change.select2', function() {
        // Reinitialize item type dropdown after AJAX load
        setTimeout(function() {
            $('#equipment_type_id').select2('destroy');
            $('#equipment_type_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Item Type',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });
            
            // Re-attach event handlers after reinitialization
            $('#equipment_type_id').on('select2:select', function() {
                console.log('Select2 item type selected after reinit:', this.value);
                this.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            $('#equipment_type_id').on('select2:clear', function() {
                console.log('Select2 item type cleared after reinit');
                this.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }, 500);
        
        // Reinitialize primary discipline dropdown after AJAX load
        setTimeout(function() {
            $('#primary_discipline').select2('destroy');
            $('#primary_discipline').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search primary discipline...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownParent: $('#primary_discipline').parent()
            });
        }, 500);
    });
    
    // Handle item type changes
    $('#equipment_type_id').on('change', function() {
        const equipmentTypeId = this.value;
        
        // Trigger subtypes loading
        if (window.loadSubtypes) {
            window.loadSubtypes(equipmentTypeId);
        }
        
        // Reinitialize subtype dropdown after AJAX load
        setTimeout(function() {
            $('#subtype_id').select2('destroy');
            $('#subtype_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Subtype',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
            });
        }, 500);
    });
    
    // Global handler to ensure search input is always visible and focused
    $(document).on('select2:open', function(e) {
        setTimeout(function() {
            var searchField = $('.select2-container--open .select2-search--dropdown .select2-search__field');
            if (searchField.length > 0) {
                searchField.attr('placeholder', 'Type to search...');
                searchField.focus();
            }
        }, 10);
    });
    
        // Debug: Log initialization
        console.log('Select2 initialized for legacy form dropdowns');
        
        // Reinitialize clear buttons after Select2 is fully loaded
        console.log('Reinitializing clear buttons after Select2...');
        setTimeout(() => {
            initializeClearButtons();
            console.log('Clear buttons reinitialized after Select2 initialization');
        }, 100);
    });
})();
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Add Legacy Item - ConstructLink™';
$pageHeader = 'Add Legacy Item';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Inventory', 'url' => '?route=assets'],
    ['title' => 'Add Legacy Item', 'url' => '?route=assets/legacy-create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>