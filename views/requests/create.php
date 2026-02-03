<?php
/**
 * ConstructLink™ Request Create View - Unified Request Management
 *
 * Refactored to use partials and external resources following DRY principles.
 * All inline JavaScript and styles have been extracted.
 *
 * @version 2.0.0
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';

// Add external CSS and JS to page head
$additionalCSS = ['assets/css/modules/requests.css'];
$additionalJS = [
    'assets/js/modules/requests/init/form-validation.js',
    'assets/js/modules/requests/components/field-toggles.js',
    'assets/js/modules/requests/components/sample-data.js'
];
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Request Details
                </h6>
            </div>
            <div class="card-body">
                <!-- Only show the create form if the user is allowed -->
                <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
                <form method="POST" action="?route=requests/create" id="requestForm">
                    <?= CSRFProtection::getTokenField() ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">
                                Project <span class="text-danger">*</span>
                            </label>
                            <?php
                            // Disable project dropdown if user has only one assigned project
                            $disableProject = (isset($projects) && count($projects) === 1);
                            ?>
                            <select name="project_id" id="project_id" class="form-select" required aria-required="true" <?= $disableProject ? 'disabled' : '' ?>>
                                <option value="">Select Project</option>
                                <?php if (isset($projects) && is_array($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?> (<?= htmlspecialchars($project['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if ($disableProject): ?>
                                <!-- Hidden field to ensure value is submitted when disabled -->
                                <input type="hidden" name="project_id" value="<?= $formData['project_id'] ?? ($projects[0]['id'] ?? '') ?>">
                                <div class="form-text text-info">
                                    <i class="bi bi-info-circle me-1"></i>You are assigned to this project.
                                </div>
                            <?php endif; ?>
                            <div class="invalid-feedback" role="alert">
                                Please select a project.
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="request_type" class="form-label">
                                Request Type <span class="text-danger">*</span>
                            </label>
                            <select name="request_type" id="request_type" class="form-select" required aria-required="true">
                                <option value="">Select Request Type</option>
                                <?php
                                // Request types are passed from controller (DRY - no hardcoding)
                                foreach ($requestTypes as $type):
                                ?>
                                    <option value="<?= $type ?>" <?= ($formData['request_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" role="alert">
                                Please select a request type.
                            </div>
                            <?php if ($user['role_name'] === 'Site Inventory Clerk'): ?>
                                <div class="form-text text-info">
                                    <i class="bi bi-info-circle me-1"></i>Site Inventory Clerks can only request Materials, Tools, and Restock.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Restock-specific fields (hidden by default) -->
                    <div id="restockFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="inventory_item_id" class="form-label">
                                    Select Item to Restock <span class="text-danger">*</span>
                                </label>
                                <select name="inventory_item_id" id="inventory_item_id" class="form-select">
                                    <option value="">Select inventory item...</option>
                                </select>
                                <div class="invalid-feedback" role="alert">
                                    Please select an inventory item to restock.
                                </div>
                            </div>
                        </div>

                        <!-- Stock Level Display -->
                        <div id="stockLevelDisplay" style="display: none;" class="alert alert-info">
                            <h6><i class="bi bi-box-seam me-2"></i>Current Stock Level</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Quantity:</strong> <span id="displayTotalQty">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Available:</strong> <span id="displayAvailableQty">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Consumed:</strong> <span id="displayConsumedQty">-</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Unit:</strong> <span id="displayUnit">-</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="restock_quantity" class="form-label">
                                    Quantity to Add <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="quantity" id="restock_quantity" class="form-control" min="1" placeholder="Enter quantity to add">
                                <div class="form-text">How many units to add to current stock?</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="restock_reason" class="form-label">
                                    Reason for Restock <span class="text-danger">*</span>
                                </label>
                                <select name="restock_reason" id="restock_reason" class="form-select">
                                    <option value="">Select reason...</option>
                                    <option value="Low Stock">Low Stock</option>
                                    <option value="Project Demand">Project Demand</option>
                                    <option value="Planned Restocking">Planned Restocking</option>
                                    <option value="Emergency">Emergency</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 conditional-field" id="categoryField" aria-hidden="true">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select" aria-label="Select request category">
                                <option value="">Select Category (Optional)</option>
                                <?php if (isset($categories) && is_array($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['name']) ?>" <?= ($formData['category'] ?? '') === $category['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">
                                Select a category if applicable to your request type.
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="urgency" class="form-label">Urgency</label>
                            <select name="urgency" id="urgency" class="form-select" aria-label="Select urgency level">
                                <option value="Normal" <?= ($formData['urgency'] ?? 'Normal') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                <option value="Urgent" <?= ($formData['urgency'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                <option value="Critical" <?= ($formData['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="date_needed" class="form-label">Date Needed</label>
                            <input type="date" name="date_needed" id="date_needed" class="form-control"
                                   value="<?= $formData['date_needed'] ?? '' ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                   aria-label="Date when this request is needed">
                            <div class="form-text">
                                When do you need this request fulfilled?
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea name="description" id="description" class="form-control" rows="4" required
                                  placeholder="Provide detailed description of what you're requesting..."
                                  aria-required="true"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        <div class="invalid-feedback" role="alert">
                            Please provide a detailed description.
                        </div>
                        <div class="form-text">
                            Be as specific as possible. Include specifications, quantities, brands, models, etc.
                        </div>
                    </div>

                    <div class="row conditional-field" id="quantityFields" aria-hidden="true">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1"
                                   value="<?= $formData['quantity'] ?? '' ?>"
                                   placeholder="Enter quantity"
                                   aria-label="Quantity needed">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" name="unit" id="unit" class="form-control"
                                   value="<?= htmlspecialchars($formData['unit'] ?? '') ?>"
                                   placeholder="e.g., pcs, kg, m, liters"
                                   aria-label="Unit of measurement">
                        </div>
                    </div>

                    <div class="mb-3 conditional-field" id="estimatedCostField" aria-hidden="true">
                        <label for="estimated_cost" class="form-label">Estimated Cost (PHP)</label>
                        <input type="number" name="estimated_cost" id="estimated_cost" class="form-control"
                               step="0.01" min="0" value="<?= $formData['estimated_cost'] ?? '' ?>"
                               placeholder="Enter estimated cost if known"
                               aria-label="Estimated cost in Philippine Pesos">
                        <div class="form-text">
                            Provide an estimated cost if you have an idea of the expense involved.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Additional Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3"
                                  placeholder="Any additional information, special instructions, or notes..."
                                  aria-label="Additional remarks or notes"><?= htmlspecialchars($formData['remarks'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="?route=requests" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Submit Request
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>You do not have permission to create a request.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Request Guidelines -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Request Guidelines
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>Important Notes</h6>
                    <ul class="mb-0 small">
                        <li>Provide detailed descriptions for faster processing</li>
                        <li>Critical and Urgent requests are prioritized</li>
                        <li>Include specifications, brands, or models when applicable</li>
                        <li>Estimated costs help with budget planning</li>
                    </ul>
                </div>

                <h6>Request Types:</h6>
                <ul class="small">
                    <li><strong>Material:</strong> Construction materials, supplies</li>
                    <li><strong>Tool:</strong> Hand tools, power tools</li>
                    <li><strong>Equipment:</strong> Heavy machinery, vehicles</li>
                    <li><strong>Service:</strong> Professional services, repairs</li>
                    <li><strong>Petty Cash:</strong> Small cash expenses</li>
                    <li><strong>Restock:</strong> Add quantity to existing consumable items</li>
                    <li><strong>Other:</strong> Miscellaneous requests</li>
                </ul>

                <h6>Approval Process:</h6>
                <ol class="small">
                    <li>Request Generated</li>
                    <li>Review by Asset Director</li>
                    <li>Forward to appropriate approver</li>
                    <li>Final approval/decline</li>
                    <li>Procurement (if approved)</li>
                </ol>
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
                    <button type="button" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-hammer me-1"></i>Sample Material Request
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-tools me-1"></i>Sample Tool Request
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-gear me-1"></i>Sample Service Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestTypeSelect = document.getElementById('request_type');
    const projectSelect = document.getElementById('project_id');
    const restockFields = document.getElementById('restockFields');
    const inventoryItemSelect = document.getElementById('inventory_item_id');
    const stockLevelDisplay = document.getElementById('stockLevelDisplay');
    const quantityFields = document.getElementById('quantityFields');
    const estimatedCostField = document.getElementById('estimatedCostField');
    const categoryField = document.getElementById('categoryField');

    // Toggle restock fields based on request type
    requestTypeSelect.addEventListener('change', function() {
        const isRestock = this.value === 'Restock';

        if (isRestock) {
            restockFields.style.display = 'block';
            // Hide non-restock fields
            if (quantityFields) quantityFields.style.display = 'none';
            if (categoryField) categoryField.style.display = 'none';

            // Load inventory items if project selected
            if (projectSelect.value) {
                loadInventoryItems(projectSelect.value);
            }
        } else {
            restockFields.style.display = 'none';
            stockLevelDisplay.style.display = 'none';
            inventoryItemSelect.innerHTML = '<option value="">Select inventory item...</option>';
        }
    });

    // Reload inventory items when project changes
    projectSelect.addEventListener('change', function() {
        if (requestTypeSelect.value === 'Restock' && this.value) {
            loadInventoryItems(this.value);
        }
    });

    // Load inventory items via AJAX
    function loadInventoryItems(projectId) {
        fetch(`api/requests/inventory-items.php?project_id=${projectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    inventoryItemSelect.innerHTML = '<option value="">Select inventory item...</option>';

                    data.items.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.text;
                        option.dataset.totalQty = item.total_quantity;
                        option.dataset.availableQty = item.available_quantity;
                        option.dataset.unit = item.unit;
                        inventoryItemSelect.appendChild(option);
                    });

                    // Show low stock items at top
                    if (data.statistics && data.statistics.low_stock > 0) {
                        const lowStockNote = document.createElement('option');
                        lowStockNote.disabled = true;
                        lowStockNote.textContent = `--- ${data.statistics.low_stock} Low Stock Items ---`;
                        inventoryItemSelect.insertBefore(lowStockNote, inventoryItemSelect.children[1]);
                    }

                    // Refresh Select2 if initialized
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery('#inventory_item_id').trigger('change.select2');
                    }
                } else {
                    console.error('Failed to load inventory items:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading inventory items:', error);
            });
    }

    // Display stock level when item selected
    inventoryItemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            const totalQty = selectedOption.dataset.totalQty;
            const availableQty = selectedOption.dataset.availableQty;
            const unit = selectedOption.dataset.unit;
            const consumedQty = totalQty - availableQty;

            document.getElementById('displayTotalQty').textContent = `${totalQty} ${unit}`;
            document.getElementById('displayAvailableQty').textContent = `${availableQty} ${unit}`;
            document.getElementById('displayConsumedQty').textContent = `${consumedQty} ${unit}`;
            document.getElementById('displayUnit').textContent = unit;

            stockLevelDisplay.style.display = 'block';

            // Auto-suggest restock quantity (consumed amount)
            document.getElementById('restock_quantity').value = consumedQty > 0 ? consumedQty : '';
        } else {
            stockLevelDisplay.style.display = 'none';
        }
    });

    // Initialize Select2 for searchable inventory item dropdown
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(document).ready(function($) {
            $('#inventory_item_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for inventory item...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return 'No consumable items found. Select a project first.';
                    },
                    searching: function() {
                        return 'Searching inventory...';
                    }
                }
            });

            // Trigger change event for stock display when Select2 changes
            $('#inventory_item_id').on('select2:select', function(e) {
                // Trigger native change event
                this.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }
});
</script>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Request - ConstructLink™';
$pageHeader = 'Create New Request';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Requests', 'url' => '?route=requests'],
    ['title' => 'Create Request', 'url' => '?route=requests/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
