<?php
/**
 * ConstructLink™ Request Create View - Unified Request Management
 */

// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle me-2"></i>
        Create New Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=requests" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Requests
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Request Details
                </h6>
            </div>
            <div class="card-body">
                <!-- Only show the create form if the user is allowed -->
                <?php if (in_array($user['role_name'], $roleConfig['requests/create'] ?? [])): ?>
                <form method="POST" action="?route=requests/create" id="requestForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="project_id" class="form-select" required>
                                <option value="">Select Project</option>
                                <?php if (isset($projects) && is_array($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>" <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?> (<?= htmlspecialchars($project['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a project.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="request_type" class="form-label">Request Type <span class="text-danger">*</span></label>
                            <select name="request_type" id="request_type" class="form-select" required onchange="toggleCategoryField()">
                                <option value="">Select Request Type</option>
                                <?php 
                                // Role-based request type restrictions
                                $allowedTypes = ['Material', 'Tool', 'Equipment', 'Service', 'Petty Cash', 'Other'];
                                
                                // Site Inventory Clerk can only request Materials and Tools
                                if ($user['role_name'] === 'Site Inventory Clerk') {
                                    $allowedTypes = ['Material', 'Tool'];
                                }
                                
                                // Project Manager restrictions (can't request Petty Cash)
                                if ($user['role_name'] === 'Project Manager') {
                                    $allowedTypes = array_diff($allowedTypes, ['Petty Cash']);
                                }
                                
                                foreach ($allowedTypes as $type): 
                                ?>
                                    <option value="<?= $type ?>" <?= ($formData['request_type'] ?? '') === $type ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a request type.
                            </div>
                            <?php if ($user['role_name'] === 'Site Inventory Clerk'): ?>
                                <div class="form-text text-info">
                                    <i class="bi bi-info-circle me-1"></i>Site Inventory Clerks can only request Materials and Tools.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3" id="categoryField" style="display: none;">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select">
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
                            <select name="urgency" id="urgency" class="form-select">
                                <option value="Normal" <?= ($formData['urgency'] ?? 'Normal') === 'Normal' ? 'selected' : '' ?>>Normal</option>
                                <option value="Urgent" <?= ($formData['urgency'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                                <option value="Critical" <?= ($formData['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="date_needed" class="form-label">Date Needed</label>
                            <input type="date" name="date_needed" id="date_needed" class="form-control" 
                                   value="<?= $formData['date_needed'] ?? '' ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="form-text">
                                When do you need this request fulfilled?
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="4" required 
                                  placeholder="Provide detailed description of what you're requesting..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a detailed description.
                        </div>
                        <div class="form-text">
                            Be as specific as possible. Include specifications, quantities, brands, models, etc.
                        </div>
                    </div>
                    
                    <div class="row" id="quantityFields" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" 
                                   value="<?= $formData['quantity'] ?? '' ?>" placeholder="Enter quantity">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" name="unit" id="unit" class="form-control" 
                                   value="<?= htmlspecialchars($formData['unit'] ?? '') ?>" placeholder="e.g., pcs, kg, m, liters">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="estimatedCostField" style="display: none;">
                        <label for="estimated_cost" class="form-label">Estimated Cost (PHP)</label>
                        <input type="number" name="estimated_cost" id="estimated_cost" class="form-control" 
                               step="0.01" min="0" value="<?= $formData['estimated_cost'] ?? '' ?>" 
                               placeholder="Enter estimated cost if known">
                        <div class="form-text">
                            Provide an estimated cost if you have an idea of the expense involved.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Additional Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3" 
                                  placeholder="Any additional information, special instructions, or notes..."><?= htmlspecialchars($formData['remarks'] ?? '') ?></textarea>
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
                <div class="alert alert-danger mt-4">You do not have permission to create a request.</div>
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
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillSampleMaterial()">
                        <i class="bi bi-hammer me-1"></i>Sample Material Request
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="fillSampleTool()">
                        <i class="bi bi-tools me-1"></i>Sample Tool Request
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="fillSampleService()">
                        <i class="bi bi-gear me-1"></i>Sample Service Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle category field based on request type
function toggleCategoryField() {
    const requestType = document.getElementById('request_type').value;
    const categoryField = document.getElementById('categoryField');
    const quantityFields = document.getElementById('quantityFields');
    const estimatedCostField = document.getElementById('estimatedCostField');
    
    // Show category for Material, Tool, Equipment
    if (['Material', 'Tool', 'Equipment'].includes(requestType)) {
        categoryField.style.display = 'block';
        quantityFields.style.display = 'block';
    } else {
        categoryField.style.display = 'none';
        quantityFields.style.display = 'none';
    }
    
    // Show estimated cost for all except Petty Cash
    if (requestType && requestType !== 'Petty Cash') {
        estimatedCostField.style.display = 'block';
    } else {
        estimatedCostField.style.display = 'none';
    }
}

// Sample data functions
function fillSampleMaterial() {
    document.getElementById('request_type').value = 'Material';
    document.getElementById('description').value = 'Portland cement bags for foundation work. Need high-grade cement suitable for structural applications.';
    document.getElementById('quantity').value = '50';
    document.getElementById('unit').value = 'bags';
    document.getElementById('estimated_cost').value = '15000';
    document.getElementById('urgency').value = 'Urgent';
    toggleCategoryField();
}

function fillSampleTool() {
    document.getElementById('request_type').value = 'Tool';
    document.getElementById('description').value = 'Heavy-duty angle grinder with cutting discs for metal fabrication work.';
    document.getElementById('quantity').value = '2';
    document.getElementById('unit').value = 'pcs';
    document.getElementById('estimated_cost').value = '8000';
    document.getElementById('urgency').value = 'Normal';
    toggleCategoryField();
}

function fillSampleService() {
    document.getElementById('request_type').value = 'Service';
    document.getElementById('description').value = 'Professional electrical inspection and certification for completed electrical installations.';
    document.getElementById('estimated_cost').value = '25000';
    document.getElementById('urgency').value = 'Normal';
    toggleCategoryField();
}

// Form validation
document.getElementById('requestForm').addEventListener('submit', function(e) {
    const projectId = document.getElementById('project_id').value;
    const requestType = document.getElementById('request_type').value;
    const description = document.getElementById('description').value.trim();
    
    if (!projectId || !requestType || !description) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    if (description.length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed description (at least 10 characters).');
        return false;
    }
});

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    toggleCategoryField();
});
</script>

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
