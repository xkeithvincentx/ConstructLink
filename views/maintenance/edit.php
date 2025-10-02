<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil me-2"></i>
        Edit Maintenance #<?= $maintenance['id'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group">
            <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Details
            </a>
            <a href="?route=maintenance" class="btn btn-outline-secondary">
                <i class="bi bi-list me-1"></i>All Maintenance
            </a>
        </div>
    </div>
</div>

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

<!-- Edit Maintenance Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Maintenance Details
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=maintenance/edit&id=<?= $maintenance['id'] ?>">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Asset (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Asset</label>
                        <div class="form-control-plaintext bg-light p-2 rounded">
                            <strong><?= htmlspecialchars($maintenance['asset_ref'] ?? '') ?></strong> - 
                            <?= htmlspecialchars($maintenance['asset_name'] ?? '') ?>
                        </div>
                        <div class="form-text">Asset cannot be changed after maintenance is created.</div>
                    </div>

                    <!-- Maintenance Type (Read-only for in-progress) -->
                    <?php if ($maintenance['status'] === 'in_progress'): ?>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Type</label>
                            <div class="form-control-plaintext bg-light p-2 rounded">
                                <?php
                                $typeClasses = [
                                    'preventive' => 'bg-info',
                                    'corrective' => 'bg-warning',
                                    'emergency' => 'bg-danger'
                                ];
                                $typeClass = $typeClasses[$maintenance['type']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $typeClass ?>">
                                    <?= ucfirst($maintenance['type']) ?>
                                </span>
                            </div>
                            <div class="form-text">Type cannot be changed once maintenance is in progress.</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="type" class="form-label">Maintenance Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="preventive" <?= ($formData['type'] ?? $maintenance['type']) === 'preventive' ? 'selected' : '' ?>>Preventive</option>
                                <option value="corrective" <?= ($formData['type'] ?? $maintenance['type']) === 'corrective' ? 'selected' : '' ?>>Corrective</option>
                                <option value="emergency" <?= ($formData['type'] ?? $maintenance['type']) === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Description is required', $errors) ? 'is-invalid' : '' ?>" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  required 
                                  placeholder="Describe the maintenance work to be performed..."><?= htmlspecialchars($formData['description'] ?? $maintenance['description']) ?></textarea>
                        <div class="invalid-feedback">
                            Please provide a description of the maintenance work.
                        </div>
                    </div>

                    <div class="row">
                        <!-- Scheduled Date -->
                        <div class="col-md-6 mb-3">
                            <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors) && in_array('Scheduled date is required', $errors) ? 'is-invalid' : '' ?>" 
                                   id="scheduled_date" 
                                   name="scheduled_date" 
                                   value="<?= htmlspecialchars($formData['scheduled_date'] ?? $maintenance['scheduled_date']) ?>" 
                                   required
                                   <?= $maintenance['status'] === 'in_progress' ? 'readonly' : '' ?>>
                            <div class="invalid-feedback">
                                Please provide a scheduled date.
                            </div>
                            <?php if ($maintenance['status'] === 'in_progress'): ?>
                                <div class="form-text">Date cannot be changed once maintenance is in progress.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?= ($formData['priority'] ?? $maintenance['priority']) === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= ($formData['priority'] ?? $maintenance['priority']) === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= ($formData['priority'] ?? $maintenance['priority']) === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= ($formData['priority'] ?? $maintenance['priority']) === 'urgent' ? 'selected' : '' ?>>Urgent</option>
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
                                   value="<?= htmlspecialchars($formData['assigned_to'] ?? $maintenance['assigned_to']) ?>" 
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
                                       value="<?= htmlspecialchars($formData['estimated_cost'] ?? $maintenance['estimated_cost']) ?>" 
                                       step="0.01" 
                                       min="0" 
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Current Status -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Maintenance ID:</dt>
                    <dd class="col-sm-7">#<?= $maintenance['id'] ?></dd>

                    <dt class="col-sm-5">Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $statusClasses = [
                            'scheduled' => 'bg-warning',
                            'in_progress' => 'bg-info',
                            'completed' => 'bg-success',
                            'canceled' => 'bg-secondary'
                        ];
                        $statusClass = $statusClasses[$maintenance['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $statusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $maintenance['status'])) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-5">Created:</dt>
                    <dd class="col-sm-7"><?= date('M j, Y', strtotime($maintenance['created_at'])) ?></dd>

                    <dt class="col-sm-5">Last Updated:</dt>
                    <dd class="col-sm-7">
                        <?= $maintenance['updated_at'] ? date('M j, Y', strtotime($maintenance['updated_at'])) : 'Never' ?>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-box me-2"></i>Asset Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row small">
                    <dt class="col-sm-5">Reference:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_ref'] ?? '') ?></dd>

                    <dt class="col-sm-5">Name:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['asset_name'] ?? '') ?></dd>

                    <dt class="col-sm-5">Category:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['category_name'] ?? '') ?></dd>

                    <dt class="col-sm-5">Project:</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($maintenance['project_name'] ?? '') ?></dd>

                    <dt class="col-sm-5">Current Status:</dt>
                    <dd class="col-sm-7">
                        <?php
                        $assetStatusClasses = [
                            'available' => 'success',
                            'in_use' => 'primary',
                            'borrowed' => 'warning',
                            'under_maintenance' => 'info',
                            'retired' => 'secondary'
                        ];
                        $assetStatusClass = $assetStatusClasses[$maintenance['asset_status'] ?? 'available'] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $assetStatusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $maintenance['asset_status'] ?? 'Available')) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Edit Guidelines -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Edit Guidelines
                </h6>
            </div>
            <div class="card-body">
                <?php if ($maintenance['status'] === 'scheduled'): ?>
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Scheduled Status:</strong> All fields can be modified.
                        </small>
                    </div>
                <?php elseif ($maintenance['status'] === 'in_progress'): ?>
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>In Progress:</strong> Type and scheduled date cannot be changed.
                        </small>
                    </div>
                <?php endif; ?>

                <h6 class="text-primary">Editable Fields:</h6>
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check text-success me-1"></i> Description</li>
                    <li><i class="bi bi-check text-success me-1"></i> Priority</li>
                    <li><i class="bi bi-check text-success me-1"></i> Assigned To</li>
                    <li><i class="bi bi-check text-success me-1"></i> Estimated Cost</li>
                    <?php if ($maintenance['status'] === 'scheduled'): ?>
                        <li><i class="bi bi-check text-success me-1"></i> Type</li>
                        <li><i class="bi bi-check text-success me-1"></i> Scheduled Date</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?route=maintenance/view&id=<?= $maintenance['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                    <a href="?route=assets/view&id=<?= $maintenance['asset_id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-box"></i> View Asset
                    </a>
                    <?php if ($maintenance['status'] === 'scheduled'): ?>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="startMaintenance()">
                            <i class="bi bi-play-circle"></i> Start Maintenance
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function startMaintenance() {
    if (confirm('Are you sure you want to start this maintenance? This will change the asset status to "Under Maintenance".')) {
        window.location.href = '?route=maintenance/start&id=<?= $maintenance['id'] ?>';
    }
}

// Set emergency priority for emergency maintenance type
document.getElementById('type')?.addEventListener('change', function() {
    const type = this.value;
    const prioritySelect = document.getElementById('priority');
    
    if (type === 'emergency' && prioritySelect.value !== 'urgent') {
        if (confirm('Emergency maintenance typically requires urgent priority. Would you like to set priority to urgent?')) {
            prioritySelect.value = 'urgent';
        }
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Maintenance #' . $maintenance['id'] . ' - ConstructLink™';
$pageHeader = 'Edit Maintenance #' . $maintenance['id'];
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Maintenance', 'url' => '?route=maintenance'],
    ['title' => 'Edit Maintenance', 'url' => '?route=maintenance/edit&id=' . $maintenance['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
