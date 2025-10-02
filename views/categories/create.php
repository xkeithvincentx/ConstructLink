<?php
// Start output buffering to capture content
ob_start();

$user = Auth::getInstance()->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle me-2"></i>
        Add New Category
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=categories" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Categories
        </a>
    </div>
</div>

<!-- Category Creation Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Category Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=categories/create" class="needs-validation" novalidate>
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Category name is required', $errors) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>" 
                                   required>
                            <div class="invalid-feedback">
                                Please provide a valid category name.
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php if (isset($parentCategories)): ?>
                                    <?php foreach ($parentCategories as $parent): ?>
                                        <option value="<?= $parent['id'] ?>" 
                                                <?= ($formData['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parent['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Select a parent category to create a subcategory</div>
                        </div>
                    </div>
                    
                    <!-- Business Classification -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-gear me-1"></i>Business Classification
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="asset_type" class="form-label">Asset Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="asset_type" name="asset_type" required 
                                            onchange="updateBusinessLogic()">
                                        <option value="">Select Asset Type</option>
                                        <option value="capital" <?= ($formData['asset_type'] ?? 'capital') === 'capital' ? 'selected' : '' ?>>
                                            🔧 Capital Assets (Depreciable Equipment)
                                        </option>
                                        <option value="inventory" <?= ($formData['asset_type'] ?? '') === 'inventory' ? 'selected' : '' ?>>
                                            📦 Inventory/Materials (Consumable Tracking)
                                        </option>
                                        <option value="expense" <?= ($formData['asset_type'] ?? '') === 'expense' ? 'selected' : '' ?>>
                                            💰 Direct Expenses (Services & Operating Costs)
                                        </option>
                                    </select>
                                    <div class="form-text">Determines accounting treatment and asset generation</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Asset Generation</label>
                                    <div class="form-check">
                                        <input type="hidden" name="generates_assets" value="0">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="generates_assets" 
                                               name="generates_assets" 
                                               value="1"
                                               <?= ($formData['generates_assets'] ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="generates_assets">
                                            Generate trackable assets
                                        </label>
                                    </div>
                                    <div class="form-text">When checked, procurement items create asset records</div>
                                </div>
                            </div>
                            
                            <!-- Expense Category (for non-asset generating) -->
                            <div id="expense_category_section" class="row mb-3" style="display: none;">
                                <div class="col-md-6">
                                    <label for="expense_category" class="form-label">Expense Category</label>
                                    <select class="form-select" id="expense_category" name="expense_category">
                                        <option value="">Select Expense Type</option>
                                        <option value="professional_services" <?= ($formData['expense_category'] ?? '') === 'professional_services' ? 'selected' : '' ?>>
                                            Professional Services
                                        </option>
                                        <option value="maintenance" <?= ($formData['expense_category'] ?? '') === 'maintenance' ? 'selected' : '' ?>>
                                            Maintenance & Repair
                                        </option>
                                        <option value="operating" <?= ($formData['expense_category'] ?? '') === 'operating' ? 'selected' : '' ?>>
                                            Operating Expenses
                                        </option>
                                        <option value="regulatory" <?= ($formData['expense_category'] ?? '') === 'regulatory' ? 'selected' : '' ?>>
                                            Regulatory & Compliance
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Asset Properties (for asset generating) -->
                            <div id="asset_properties_section" class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="hidden" name="is_consumable" value="0">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="is_consumable" 
                                                   name="is_consumable" 
                                                   value="1"
                                                   <?= ($formData['is_consumable'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_consumable">
                                                Consumable Items
                                            </label>
                                        </div>
                                        <div class="form-text">Items that are used up over time</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="hidden" name="depreciation_applicable" value="0">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="depreciation_applicable" 
                                                   name="depreciation_applicable" 
                                                   value="1"
                                                   <?= ($formData['depreciation_applicable'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="depreciation_applicable">
                                                Subject to Depreciation
                                            </label>
                                        </div>
                                        <div class="form-text">Applicable to capital assets</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="hidden" name="auto_expense_below_threshold" value="0">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="auto_expense_below_threshold" 
                                                   name="auto_expense_below_threshold" 
                                                   value="1"
                                                   <?= ($formData['auto_expense_below_threshold'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="auto_expense_below_threshold">
                                                Auto-expense below threshold
                                            </label>
                                        </div>
                                        <div class="form-text">Automatically expense low-value items</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Capitalization Threshold -->
                            <div id="capitalization_section" class="row mb-3">
                                <div class="col-md-6">
                                    <label for="capitalization_threshold" class="form-label">Capitalization Threshold</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                               class="form-control" 
                                               id="capitalization_threshold" 
                                               name="capitalization_threshold" 
                                               min="0" 
                                               step="0.01"
                                               value="<?= htmlspecialchars($formData['capitalization_threshold'] ?? '0.00') ?>"
                                               placeholder="0.00">
                                    </div>
                                    <div class="form-text">Minimum value to create asset (0 = always create asset)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Descriptions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="description" class="form-label">General Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Enter category description..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="business_description" class="form-label">Business Usage Guidelines</label>
                            <textarea class="form-control" 
                                      id="business_description" 
                                      name="business_description" 
                                      rows="3"
                                      placeholder="Explain business context and usage guidelines..."><?= htmlspecialchars($formData['business_description'] ?? '') ?></textarea>
                            <div class="form-text">Helps users understand when to use this category</div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar with Help -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Category Creation Guide
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Required Fields</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i> Category Name</li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6>Category Types</h6>
                    <p class="small text-muted">
                        <strong>Equipment:</strong> Durable items like machinery, tools, vehicles<br>
                        <strong>Consumable:</strong> Items that are used up like materials, supplies, fuel
                    </p>
                </div>
                
                <div class="mb-3">
                    <h6>Hierarchy</h6>
                    <p class="small text-muted">
                        You can create subcategories by selecting a parent category. This helps organize related items.
                    </p>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-lightbulb me-1"></i>
                        <strong>Tip:</strong> Use clear, descriptive names for categories to make asset management easier.
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Category Examples -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>Category Examples
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <h6>Equipment Categories:</h6>
                    <ul class="list-unstyled text-muted">
                        <li>• Heavy Machinery</li>
                        <li>• Hand Tools</li>
                        <li>• Vehicles</li>
                        <li>• Safety Equipment</li>
                    </ul>
                    
                    <h6>Consumable Categories:</h6>
                    <ul class="list-unstyled text-muted">
                        <li>• Construction Materials</li>
                        <li>• Office Supplies</li>
                        <li>• Fuel & Lubricants</li>
                        <li>• Safety Supplies</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
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
</script>

<script>
function updateBusinessLogic() {
    const assetType = document.getElementById('asset_type').value;
    const generatesAssets = document.getElementById('generates_assets');
    const expenseCategorySection = document.getElementById('expense_category_section');
    const assetPropertiesSection = document.getElementById('asset_properties_section');
    const capitalizationSection = document.getElementById('capitalization_section');
    const depreciationApplicable = document.getElementById('depreciation_applicable');
    
    // Update based on asset type
    if (assetType === 'expense') {
        // Direct expense - disable asset generation
        generatesAssets.checked = false;
        generatesAssets.disabled = true;
        expenseCategorySection.style.display = 'block';
        assetPropertiesSection.style.display = 'none';
        capitalizationSection.style.display = 'none';
        
        // Make expense category required
        document.getElementById('expense_category').required = true;
    } else {
        // Asset-generating type
        generatesAssets.checked = true;
        generatesAssets.disabled = false;
        expenseCategorySection.style.display = 'none';
        assetPropertiesSection.style.display = 'block';
        capitalizationSection.style.display = 'block';
        
        // Make expense category not required
        document.getElementById('expense_category').required = false;
        
        // Set depreciation based on capital vs inventory
        if (assetType === 'capital') {
            depreciationApplicable.checked = true;
            depreciationApplicable.disabled = false;
        } else if (assetType === 'inventory') {
            depreciationApplicable.checked = false;
            depreciationApplicable.disabled = true;
            
            // Inventory items are typically consumable
            document.getElementById('is_consumable').checked = true;
        }
    }
    
    // Update asset generation visibility based on checkbox
    updateAssetGenerationLogic();
}

function updateAssetGenerationLogic() {
    const generatesAssets = document.getElementById('generates_assets').checked;
    const expenseCategorySection = document.getElementById('expense_category_section');
    const assetPropertiesSection = document.getElementById('asset_properties_section');
    const capitalizationSection = document.getElementById('capitalization_section');
    
    if (generatesAssets) {
        expenseCategorySection.style.display = 'none';
        assetPropertiesSection.style.display = 'block';
        capitalizationSection.style.display = 'block';
        document.getElementById('expense_category').required = false;
    } else {
        expenseCategorySection.style.display = 'block';
        assetPropertiesSection.style.display = 'none';
        capitalizationSection.style.display = 'none';
        document.getElementById('expense_category').required = true;
    }
}

// Initialize form on load and add event listeners
document.addEventListener('DOMContentLoaded', function() {
    updateBusinessLogic();
    
    // Add event listeners
    document.getElementById('generates_assets').addEventListener('change', updateAssetGenerationLogic);
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Category - ConstructLink™';
$pageHeader = 'Create New Category';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Categories', 'url' => '?route=categories'],
    ['title' => 'Create Category', 'url' => '?route=categories/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
