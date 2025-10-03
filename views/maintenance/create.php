<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Success Messages -->
<?php if (!empty($messages)): ?>
    <div class="alert alert-success">
        <?php foreach ($messages as $message): ?>
            <div><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Maintenance Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Maintenance Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=maintenance/create">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <div class="row">
                        <!-- Asset Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors) && in_array('Asset is required', $errors) ? 'is-invalid' : '' ?>" 
                                    id="asset_id" 
                                    name="asset_id" 
                                    required>
                                <option value="">Select Asset</option>
                                <?php if (isset($assets) && is_array($assets)): ?>
                                    <?php foreach ($assets as $asset): ?>
                                        <option value="<?= $asset['id'] ?>" 
                                                <?= ($formData['asset_id'] ?? '') == $asset['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($asset['ref']) ?> - <?= htmlspecialchars($asset['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select an asset.
                            </div>
                        </div>

                        <!-- Maintenance Type -->
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="preventive" <?= ($formData['type'] ?? 'preventive') === 'preventive' ? 'selected' : '' ?>>Preventive</option>
                                <option value="corrective" <?= ($formData['type'] ?? '') === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                                <option value="emergency" <?= ($formData['type'] ?? '') === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Description is required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  required 
                                  placeholder="Describe the maintenance work to be performed..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a description of the maintenance work.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Scheduled Date -->
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="scheduled_date" 
                                   name="scheduled_date" 
                                   value="<?= htmlspecialchars($formData['scheduled_date'] ?? date('Y-m-d')) ?>" 
                                   required>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?= ($formData['priority'] ?? 'medium') === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($formData['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($formData['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= ($formData['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Assigned To -->
                        <div class="col-md-6 mb-3">
                            <label for="assigned_to" class="form-label">Assigned To</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="assigned_to" 
                                   name="assigned_to" 
                                   value="<?= htmlspecialchars($formData['assigned_to'] ?? '') ?>" 
                                   placeholder="Technician or team name">
                        </div>

                        <!-- Estimated Cost -->
                        <div class="col-md-6 mb-3">
                            <label for="estimated_cost" class="form-label">Estimated Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="estimated_cost" 
                                       name="estimated_cost" 
                                       value="<?= htmlspecialchars($formData['estimated_cost'] ?? '') ?>" 
                                       step="0.01" 
                                       min="0" 
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=maintenance" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Schedule Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Panel -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Maintenance Types
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-info">Preventive</h6>
                    <p class="small text-muted">Regular scheduled maintenance to prevent issues and extend asset life.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-warning">Corrective</h6>
                    <p class="small text-muted">Maintenance to fix identified issues or restore normal operation.</p>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-danger">Emergency</h6>
                    <p class="small text-muted">Urgent maintenance required due to critical failure or safety concerns.</p>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Priority Levels
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-success me-2">Low</span>
                    <small>Can be scheduled flexibly</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-warning me-2">Medium</span>
                    <small>Should be completed within planned timeframe</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-danger me-2">High</span>
                    <small>Requires prompt attention</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-dark me-2">Urgent</span>
                    <small>Critical - immediate action required</small>
                </div>
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
                    <a href="?route=maintenance" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list"></i> View All Maintenance
                    </a>
                    <a href="?route=assets" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-box"></i> Browse Assets
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-populate asset details when asset is selected
document.getElementById('asset_id').addEventListener('change', function() {
    const assetId = this.value;
    if (assetId) {
        // You can add AJAX call here to get asset details if needed
        console.log('Asset selected:', assetId);
    }
});

// Set emergency priority for emergency maintenance type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const prioritySelect = document.getElementById('priority');
    
    if (type === 'emergency') {
        prioritySelect.value = 'urgent';
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Schedule Maintenance - ConstructLink™';
$pageHeader = 'Schedule Maintenance';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Maintenance', 'url' => '?route=maintenance'],
    ['title' => 'Schedule Maintenance', 'url' => '?route=maintenance/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
