<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-pencil me-2"></i>
        Edit Manufacturer
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=makers/view&id=<?= $maker['id'] ?>" class="btn btn-outline-info me-2">
            <i class="bi bi-eye me-1"></i>View Details
        </a>
        <a href="?route=makers" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Manufacturers
        </a>
    </div>
</div>

<!-- Error Messages -->
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

<!-- Success Messages -->
<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Main Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-gear me-2"></i>Edit Manufacturer Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=makers/edit&id=<?= $maker['id'] ?>" id="editMakerForm">
                    <?= CSRFProtection::generateToken() ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Manufacturer Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?= isset($errors) && in_array('Manufacturer name is required', $errors) ? 'is-invalid' : '' ?>"
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                                       required
                                       maxlength="200"
                                       placeholder="Enter manufacturer name">
                                <div class="invalid-feedback">
                                    Please provide a valid manufacturer name.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" 
                                       class="form-control"
                                       id="country" 
                                       name="country" 
                                       value="<?= htmlspecialchars($formData['country'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Enter country of origin">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="website" class="form-label">Website URL</label>
                        <input type="url" 
                               class="form-control <?= isset($errors) && in_array('Invalid website URL format', $errors) ? 'is-invalid' : '' ?>"
                               id="website" 
                               name="website" 
                               value="<?= htmlspecialchars($formData['website'] ?? '') ?>"
                               maxlength="255"
                               placeholder="https://www.example.com">
                        <div class="invalid-feedback">
                            Please provide a valid website URL.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="Enter manufacturer description, specialties, or notes..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        <div class="form-text">
                            <span id="descriptionCount">0</span>/1000 characters
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?route=makers/view&id=<?= $maker['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Manufacturer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Current Information -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Current Information
                </h6>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">ID:</dt>
                    <dd class="col-sm-7">#<?= $maker['id'] ?></dd>
                    
                    <dt class="col-sm-5">Created:</dt>
                    <dd class="col-sm-7"><?= formatDate($maker['created_at']) ?></dd>
                    
                    <?php if ($maker['updated_at']): ?>
                        <dt class="col-sm-5">Last Updated:</dt>
                        <dd class="col-sm-7"><?= formatDate($maker['updated_at']) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-5">Assets:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-info"><?= $maker['assets_count'] ?? 0 ?> assets</span>
                    </dd>
                    
                    <dt class="col-sm-5">Total Value:</dt>
                    <dd class="col-sm-7">
                        <strong><?= formatCurrency($maker['total_value'] ?? 0) ?></strong>
                    </dd>
                </dl>
            </div>
        </div>
        
        <!-- Help Card -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-question-circle me-2"></i>Help & Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6>Required Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-2"></i>Manufacturer Name</li>
                </ul>
                
                <h6 class="mt-3">Optional Information:</h6>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-2"></i>Country of Origin</li>
                    <li><i class="bi bi-check text-success me-2"></i>Official Website</li>
                    <li><i class="bi bi-check text-success me-2"></i>Description & Notes</li>
                </ul>
                
                <?php if (($maker['assets_count'] ?? 0) > 0): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This manufacturer has <?= $maker['assets_count'] ?> associated assets. Changes may affect asset records.
                    </div>
                <?php endif; ?>
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
                    <a href="?route=makers/view&id=<?= $maker['id'] ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>
                    <a href="?route=makers" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list me-1"></i>All Manufacturers
                    </a>
                    <?php if (($maker['assets_count'] ?? 0) > 0): ?>
                        <a href="?route=assets&maker_id=<?= $maker['id'] ?>" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-box me-1"></i>View Assets (<?= $maker['assets_count'] ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for description
    const descriptionTextarea = document.getElementById('description');
    const descriptionCount = document.getElementById('descriptionCount');
    
    function updateDescriptionCount() {
        const count = descriptionTextarea.value.length;
        descriptionCount.textContent = count;
        
        if (count > 900) {
            descriptionCount.classList.add('text-warning');
        } else {
            descriptionCount.classList.remove('text-warning');
        }
        
        if (count >= 1000) {
            descriptionCount.classList.add('text-danger');
            descriptionCount.classList.remove('text-warning');
        }
    }
    
    descriptionTextarea.addEventListener('input', updateDescriptionCount);
    updateDescriptionCount(); // Initial count
    
    // Form validation
    const form = document.getElementById('editMakerForm');
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const website = document.getElementById('website').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Manufacturer name is required.');
            document.getElementById('name').focus();
            return false;
        }
        
        if (website && !isValidUrl(website)) {
            e.preventDefault();
            alert('Please enter a valid website URL.');
            document.getElementById('website').focus();
            return false;
        }
    });
    
    // URL validation helper
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Auto-format website URL
    const websiteInput = document.getElementById('website');
    websiteInput.addEventListener('blur', function() {
        let url = this.value.trim();
        if (url && !url.startsWith('http://') && !url.startsWith('https://')) {
            this.value = 'https://' + url;
        }
    });
    
    // Auto-capitalize manufacturer name
    const nameInput = document.getElementById('name');
    nameInput.addEventListener('blur', function() {
        this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
    });
    
    // Confirm navigation away with unsaved changes
    let formChanged = false;
    const formInputs = form.querySelectorAll('input, textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            formChanged = true;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    form.addEventListener('submit', function() {
        formChanged = false;
    });
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Edit Manufacturer - ConstructLink™';
$pageHeader = 'Edit Manufacturer: ' . htmlspecialchars($maker['name']);
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Manufacturers', 'url' => '?route=makers'],
    ['title' => 'Edit Manufacturer', 'url' => '?route=makers/edit&id=' . $maker['id']]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
