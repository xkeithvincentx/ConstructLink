<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-plus-circle me-2"></i>
        Create Withdrawal Request
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=withdrawals" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Withdrawals
        </a>
    </div>
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
        <!-- Withdrawal Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Withdrawal Request Details
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=withdrawals/create" id="withdrawalForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <!-- Project Selection (First) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors) && in_array('Project is required', $errors) ? 'is-invalid' : '' ?>" 
                                        id="project_id" name="project_id" required onchange="loadProjectAssets()">
                                    <option value="">Select Project</option>
                                    <?php if (isset($projects) && is_array($projects)): ?>
                                        <?php foreach ($projects as $project): ?>
                                            <option value="<?= $project['id'] ?>" 
                                                    <?= ($formData['project_id'] ?? '') == $project['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($project['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a project.
                                </div>
                                <div class="form-text">
                                    Select the project first to see available assets.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Asset Selection (Second) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors) && in_array('Asset is required', $errors) ? 'is-invalid' : '' ?>" 
                                        id="asset_id" name="asset_id" required onchange="updateAssetInfo()" disabled>
                                    <option value="">Select Project First</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select an asset.
                                </div>
                                <div class="form-text">
                                    Only available assets from the selected project are shown.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Receiver Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="receiver_name" class="form-label">Receiver Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control <?= isset($errors) && in_array('Receiver name is required', $errors) ? 'is-invalid' : '' ?>" 
                                       id="receiver_name" 
                                       name="receiver_name" 
                                       value="<?= htmlspecialchars($formData['receiver_name'] ?? '') ?>" 
                                       required
                                       placeholder="Enter receiver's full name">
                                <div class="invalid-feedback">
                                    Please enter the receiver's name.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Expected Return Date -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expected_return" class="form-label">Expected Return Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="expected_return" 
                                       name="expected_return" 
                                       value="<?= htmlspecialchars($formData['expected_return'] ?? '') ?>"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                <div class="form-text">
                                    Optional: When do you expect to return this asset?
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purpose -->
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Purpose is required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="purpose" 
                                  name="purpose" 
                                  rows="3" 
                                  required
                                  placeholder="Describe the purpose for withdrawing this asset..."><?= htmlspecialchars($formData['purpose'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please describe the purpose of this withdrawal.
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="2" 
                                  placeholder="Any additional notes or special instructions..."><?= htmlspecialchars($formData['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=withdrawals" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-circle me-1"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Asset Information Panel -->
        <div class="card" id="assetInfoPanel" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7" id="assetRef">-</dd>
                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7" id="assetCategory">-</dd>
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7" id="assetProject">-</dd>
                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-success">Available</span>
                    </dd>
                </dl>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Withdrawal Workflow (MVA)
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li><i class="bi bi-person-plus text-primary me-1"></i> <strong>Maker:</strong> Warehouseman/Site Inventory Clerk initiates withdrawal (Pending Verification)</li>
                    <li><i class="bi bi-person-check text-info me-1"></i> <strong>Verifier:</strong> Site Inventory Clerk/Project Manager verifies (Pending Approval)</li>
                    <li><i class="bi bi-person-check-fill text-success me-1"></i> <strong>Authorizer:</strong> Project Manager authorizes (Approved)</li>
                    <li><i class="bi bi-box-arrow-in-right text-secondary me-1"></i> <strong>Releaser:</strong> Asset Director/Warehouseman releases (Released)</li>
                    <li><i class="bi bi-arrow-return-left text-success me-1"></i> <strong>Completer:</strong> Asset returned (Returned)</li>
                    <li><i class="bi bi-x-circle text-danger me-1"></i> <strong>Canceled:</strong> Request canceled (Canceled)</li>
                </ul>
            </div>
        </div>
        
        <!-- Project Assets Count -->
        <div class="card mt-3" id="projectStatsPanel" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2"></i>Project Assets
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="mb-1" id="availableCount">0</h4>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="mb-1" id="totalCount">0</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Store all assets data
let allAssets = <?= json_encode($assets ?? []) ?>;

// Load assets for selected project
function loadProjectAssets() {
    const projectSelect = document.getElementById('project_id');
    const assetSelect = document.getElementById('asset_id');
    const projectStatsPanel = document.getElementById('projectStatsPanel');
    const submitBtn = document.getElementById('submitBtn');
    
    const projectId = projectSelect.value;
    
    // Clear asset selection
    assetSelect.innerHTML = '<option value="">Loading...</option>';
    assetSelect.disabled = true;
    submitBtn.disabled = true;
    
    // Hide panels
    document.getElementById('assetInfoPanel').style.display = 'none';
    projectStatsPanel.style.display = 'none';
    
    if (!projectId) {
        assetSelect.innerHTML = '<option value="">Select Project First</option>';
        return;
    }
    
    // Filter assets by project
    const projectAssets = allAssets.filter(asset => asset.project_id == projectId);
    
    // Populate asset dropdown
    assetSelect.innerHTML = '<option value="">Select Asset</option>';
    projectAssets.forEach(asset => {
        const option = document.createElement('option');
        option.value = asset.id;
        option.textContent = `${asset.ref} - ${asset.name}`;
        option.dataset.ref = asset.ref;
        option.dataset.category = asset.category_name || 'N/A';
        option.dataset.project = asset.project_name || 'N/A';
        assetSelect.appendChild(option);
    });
    
    assetSelect.disabled = false;
    
    // Update project stats
    document.getElementById('availableCount').textContent = projectAssets.length;
    document.getElementById('totalCount').textContent = projectAssets.length;
    projectStatsPanel.style.display = 'block';
    
    // Update submit button state
    updateSubmitButton();
}

// Update asset information when asset is selected
function updateAssetInfo() {
    const assetSelect = document.getElementById('asset_id');
    const selectedOption = assetSelect.options[assetSelect.selectedIndex];
    const assetInfoPanel = document.getElementById('assetInfoPanel');
    
    if (selectedOption.value) {
        // Show asset info panel
        assetInfoPanel.style.display = 'block';
        
        // Update asset information
        document.getElementById('assetRef').textContent = selectedOption.dataset.ref || '-';
        document.getElementById('assetCategory').textContent = selectedOption.dataset.category || '-';
        document.getElementById('assetProject').textContent = selectedOption.dataset.project || '-';
    } else {
        // Hide asset info panel
        assetInfoPanel.style.display = 'none';
    }
    
    updateSubmitButton();
}

// Update submit button state
function updateSubmitButton() {
    const projectId = document.getElementById('project_id').value;
    const assetId = document.getElementById('asset_id').value;
    const receiverName = document.getElementById('receiver_name').value.trim();
    const purpose = document.getElementById('purpose').value.trim();
    const submitBtn = document.getElementById('submitBtn');
    
    submitBtn.disabled = !(projectId && assetId && receiverName && purpose);
}

// Form validation
document.getElementById('withdrawalForm').addEventListener('submit', function(e) {
    const assetId = document.getElementById('asset_id').value;
    const projectId = document.getElementById('project_id').value;
    const receiverName = document.getElementById('receiver_name').value.trim();
    const purpose = document.getElementById('purpose').value.trim();
    
    if (!assetId || !projectId || !receiverName || !purpose) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
    
    // Validate expected return date if provided
    const expectedReturn = document.getElementById('expected_return').value;
    if (expectedReturn) {
        const returnDate = new Date(expectedReturn);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (returnDate <= today) {
            e.preventDefault();
            alert('Expected return date must be in the future.');
            return false;
        }
    }
    
    // Final confirmation
    if (!confirm('Are you sure you want to submit this withdrawal request?')) {
        e.preventDefault();
        return false;
    }
});

// Real-time validation
document.getElementById('receiver_name').addEventListener('input', updateSubmitButton);
document.getElementById('purpose').addEventListener('input', updateSubmitButton);

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    // If project is pre-selected, load assets
    const projectSelect = document.getElementById('project_id');
    if (projectSelect.value) {
        loadProjectAssets();
        
        // If asset is also pre-selected, update info
        setTimeout(() => {
            const assetSelect = document.getElementById('asset_id');
            if (assetSelect.value) {
                updateAssetInfo();
            }
        }, 100);
    }
    
    updateSubmitButton();
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
