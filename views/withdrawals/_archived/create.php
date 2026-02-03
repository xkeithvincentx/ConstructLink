<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

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
                                    Select the project first to see available consumables.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Consumable Selection (Second) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="asset_id" class="form-label">Consumable Item <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors) && in_array('Consumable is required', $errors) ? 'is-invalid' : '' ?>"
                                        id="asset_id" name="asset_id" required onchange="updateConsumableInfo()" disabled>
                                    <option value="">Select Project First</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a consumable item.
                                </div>
                                <div class="form-text">
                                    Only available consumables from the selected project are shown.
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
                                    Optional: When do you expect to return this consumable?
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
                                  placeholder="Describe the purpose for withdrawing this consumable..."><?= htmlspecialchars($formData['purpose'] ?? '') ?></textarea>
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
        <!-- Consumable Information Panel -->
        <div class="card" id="consumableInfoPanel" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Consumable Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7" id="consumableRef">-</dd>
                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7" id="consumableCategory">-</dd>
                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7" id="consumableProject">-</dd>
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
                    <li><i class="bi bi-arrow-return-left text-success me-1"></i> <strong>Completer:</strong> Consumable returned (Returned)</li>
                    <li><i class="bi bi-x-circle text-danger me-1"></i> <strong>Canceled:</strong> Request canceled (Canceled)</li>
                </ul>
            </div>
        </div>

        <!-- Project Consumables Count -->
        <div class="card mt-3" id="projectStatsPanel" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2"></i>Project Consumables
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
// Store all consumables data
let allConsumables = <?= json_encode($assets ?? []) ?>;

// Load consumables for selected project
function loadProjectAssets() {
    const projectSelect = document.getElementById('project_id');
    const consumableSelect = document.getElementById('asset_id');
    const projectStatsPanel = document.getElementById('projectStatsPanel');
    const submitBtn = document.getElementById('submitBtn');

    const projectId = projectSelect.value;

    // Clear consumable selection
    consumableSelect.innerHTML = '<option value="">Loading...</option>';
    consumableSelect.disabled = true;
    submitBtn.disabled = true;

    // Hide panels
    document.getElementById('consumableInfoPanel').style.display = 'none';
    projectStatsPanel.style.display = 'none';

    if (!projectId) {
        consumableSelect.innerHTML = '<option value="">Select Project First</option>';
        return;
    }

    // Filter consumables by project
    const projectConsumables = allConsumables.filter(consumable => consumable.project_id == projectId);

    // Populate consumable dropdown
    consumableSelect.innerHTML = '<option value="">Select Consumable</option>';
    projectConsumables.forEach(consumable => {
        const option = document.createElement('option');
        option.value = consumable.id;
        option.textContent = `${consumable.ref} - ${consumable.name}`;
        option.dataset.ref = consumable.ref;
        option.dataset.category = consumable.category_name || 'N/A';
        option.dataset.project = consumable.project_name || 'N/A';
        consumableSelect.appendChild(option);
    });

    consumableSelect.disabled = false;

    // Update project stats
    document.getElementById('availableCount').textContent = projectConsumables.length;
    document.getElementById('totalCount').textContent = projectConsumables.length;
    projectStatsPanel.style.display = 'block';

    // Update submit button state
    updateSubmitButton();
}

// Update consumable information when consumable is selected
function updateConsumableInfo() {
    const consumableSelect = document.getElementById('asset_id');
    const selectedOption = consumableSelect.options[consumableSelect.selectedIndex];
    const consumableInfoPanel = document.getElementById('consumableInfoPanel');

    if (selectedOption.value) {
        // Show consumable info panel
        consumableInfoPanel.style.display = 'block';

        // Update consumable information
        document.getElementById('consumableRef').textContent = selectedOption.dataset.ref || '-';
        document.getElementById('consumableCategory').textContent = selectedOption.dataset.category || '-';
        document.getElementById('consumableProject').textContent = selectedOption.dataset.project || '-';
    } else {
        // Hide consumable info panel
        consumableInfoPanel.style.display = 'none';
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
    // If project is pre-selected, load consumables
    const projectSelect = document.getElementById('project_id');
    if (projectSelect.value) {
        loadProjectAssets();

        // If consumable is also pre-selected, update info
        setTimeout(() => {
            const consumableSelect = document.getElementById('asset_id');
            if (consumableSelect.value) {
                updateConsumableInfo();
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
