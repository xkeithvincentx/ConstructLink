<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil-square me-2"></i>
        Edit Asset: <?= htmlspecialchars($asset['name'] ?? 'Unknown') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=assets/view&id=<?= $asset['id'] ?? 0 ?>" class="btn btn-outline-info me-2">
            <i class="bi bi-eye me-1"></i>View Asset
        </a>
        <a href="?route=assets" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Assets
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
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Asset Edit Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Edit Asset Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=assets/edit&id=<?= $asset['id'] ?>" class="needs-validation" novalidate x-data="assetEditForm()">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control <?= isset($errors) && in_array('Asset name is required', $errors) ? 'is-invalid' : '' ?>"
                                   id="name"
                                   name="name"
                                   value="<?= htmlspecialchars($asset['name']) ?>"
                                   required
                                   x-model="formData.name">
                            <div class="invalid-feedback">
                                Please provide a valid asset name.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="ref" class="form-label">Asset Reference</label>
                            <input type="text"
                                   class="form-control"
                                   id="ref"
                                   value="<?= htmlspecialchars($asset['ref']) ?>"
                                   readonly>
                            <div class="form-text">Asset reference cannot be changed</div>
                        </div>
                    </div>

                    <!-- Category and Project -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors) && in_array('Category is required', $errors) ? 'is-invalid' : '' ?>"
                                    id="category_id"
                                    name="category_id"
                                    required
                                    x-model="formData.category_id">
                                <option value="">Select Category</option>
                                <?php if (isset($categories) && is_array($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"
                                                <?= $asset['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['display_name'] ?? $category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a category.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors) && in_array('Project is required', $errors) ? 'is-invalid' : '' ?>"
                                    id="project_id"
                                    name="project_id"
                                    required
                                    x-model="formData.project_id">
                                <option value="">Select Project</option>
                                <?php if (isset($projects) && is_array($projects)): ?>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?= $project['id'] ?>"
                                                <?= $asset['project_id'] == $project['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($project['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a project.
                            </div>
                        </div>
                    </div>

                    <!-- Manufacturer and Vendor -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="maker_id" class="form-label">Manufacturer</label>
                            <select class="form-select" 
                                    id="maker_id" 
                                    name="maker_id"
                                    x-model="formData.maker_id">
                                <option value="">Select Manufacturer</option>
                                <?php if (isset($makers) && is_array($makers)): ?>
                                    <?php foreach ($makers as $maker): ?>
                                        <option value="<?= $maker['id'] ?>"
                                                <?= $asset['maker_id'] == $maker['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($maker['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select class="form-select" 
                                    id="vendor_id" 
                                    name="vendor_id"
                                    x-model="formData.vendor_id">
                                <option value="">Select Vendor</option>
                                <?php if (isset($vendors) && is_array($vendors)): ?>
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

                    <!-- Model and Serial Number -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="model" class="form-label">Model</label>
                            <input type="text"
                                   class="form-control"
                                   id="model"
                                   name="model"
                                   value="<?= htmlspecialchars($asset['model'] ?? '') ?>"
                                   x-model="formData.model">
                        </div>
                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text"
                                   class="form-control"
                                   id="serial_number"
                                   name="serial_number"
                                   value="<?= htmlspecialchars($asset['serial_number'] ?? '') ?>"
                                   x-model="formData.serial_number">
                        </div>
                    </div>

                    <!-- Acquisition Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="acquired_date" class="form-label">Acquired Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control <?= isset($errors) && in_array('Acquired date is required', $errors) ? 'is-invalid' : '' ?>"
                                   id="acquired_date"
                                   name="acquired_date"
                                   value="<?= $asset['acquired_date'] ?>"
                                   required
                                   x-model="formData.acquired_date">
                            <div class="invalid-feedback">
                                Please provide the acquisition date.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="acquisition_cost" class="form-label">Acquisition Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number"
                                       class="form-control"
                                       id="acquisition_cost"
                                       name="acquisition_cost"
                                       value="<?= $asset['acquisition_cost'] ?? '' ?>"
                                       step="0.01"
                                       min="0"
                                       x-model="formData.acquisition_cost">
                            </div>
                        </div>
                    </div>

                    <!-- Client-Supplied Asset -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_client_supplied" 
                                       name="is_client_supplied"
                                       <?= $asset['is_client_supplied'] ? 'checked' : '' ?>
                                       x-model="formData.is_client_supplied">
                                <label class="form-check-label" for="is_client_supplied">
                                    Client-Supplied Asset
                                </label>
                            </div>
                            <div class="form-text">Check if this asset is supplied by the client</div>
                        </div>
                        
                        <div class="col-md-6" x-show="formData.is_client_supplied">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" 
                                    id="client_id" 
                                    name="client_id"
                                    x-model="formData.client_id">
                                <option value="">Select Client</option>
                                <?php if (isset($clients) && is_array($clients)): ?>
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

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Detailed description of the asset"
                                  x-model="formData.description"><?= htmlspecialchars($asset['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Asset
                        </button>
                        <a href="?route=assets/view&id=<?= $asset['id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Asset Status -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Status
                </h6>
            </div>
            <div class="card-body">
                <?php
                $status = $asset['status'] ?? 'unknown';
                $statusClass = getStatusBadgeClass($status);
                ?>
                <span class="badge <?= $statusClass ?> fs-6 mb-3">
                    <?= getStatusLabel($status) ?>
                </span>
                
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Created:</span>
                        <span><?= formatDate($asset['created_at'] ?? '') ?></span>
                    </div>
                    <?php if ($asset['updated_at'] ?? false): ?>
                        <div class="d-flex justify-content-between">
                            <span>Last Updated:</span>
                            <span><?= formatDate($asset['updated_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Edit Guidelines -->
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="card-title mb-0">
                    <i class="bi bi-lightbulb me-2"></i>Edit Guidelines
                </h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled small">
                    <li><i class="bi bi-check-circle text-success me-1"></i> Asset reference cannot be changed</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Required fields must be filled</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Changes are logged for audit</li>
                    <li><i class="bi bi-check-circle text-success me-1"></i> Status changes require separate action</li>
                </ul>
                
                <hr class="my-3">
                
                <div class="alert alert-info">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Note:</strong> Changing project assignment may affect asset availability and reporting.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function assetEditForm() {
    return {
        formData: {
            name: '<?= htmlspecialchars($asset['name'] ?? '') ?>',
            category_id: '<?= $asset['category_id'] ?? '' ?>',
            project_id: '<?= $asset['project_id'] ?? '' ?>',
            maker_id: '<?= $asset['maker_id'] ?? '' ?>',
            vendor_id: '<?= $asset['vendor_id'] ?? '' ?>',
            client_id: '<?= $asset['client_id'] ?? '' ?>',
            model: '<?= htmlspecialchars($asset['model'] ?? '') ?>',
            serial_number: '<?= htmlspecialchars($asset['serial_number'] ?? '') ?>',
            acquired_date: '<?= $asset['acquired_date'] ?? '' ?>',
            acquisition_cost: '<?= $asset['acquisition_cost'] ?? '' ?>',
            is_client_supplied: <?= ($asset['is_client_supplied'] ?? false) ? 'true' : 'false' ?>,
            description: '<?= htmlspecialchars($asset['description'] ?? '') ?>'
        }
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Bootstrap form validation
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
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Asset - ConstructLink™';
$pageHeader = 'Edit Asset: ' . htmlspecialchars($asset['name'] ?? 'Unknown');
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Assets', 'url' => '?route=assets'],
    ['title' => 'Edit Asset', 'url' => '?route=assets/edit&id=' . ($asset['id'] ?? 0)]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
