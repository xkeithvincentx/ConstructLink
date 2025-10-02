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
        Create New Project
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=projects" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Projects
        </a>
    </div>
</div>

<!-- Display Messages -->
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

<!-- Project Creation Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-form-check me-2"></i>Project Information
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="?route=projects/create" class="needs-validation" novalidate id="projectForm">
                    <?= CSRFProtection::getTokenField() ?>
                    
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Name is required', $errors) ? 'is-invalid' : '' ?>"
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                                   required
                                   maxlength="200"
                                   placeholder="Enter descriptive project name">
                            <div class="invalid-feedback">
                                Please provide a valid project name.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="code" class="form-label">Project Code <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors) && in_array('Code is required', $errors) ? 'is-invalid' : '' ?>"
                                   id="code" 
                                   name="code" 
                                   value="<?= htmlspecialchars($formData['code'] ?? '') ?>"
                                   required
                                   maxlength="20"
                                   placeholder="e.g., PROJ001"
                                   style="text-transform: uppercase;">
                            <div class="form-text">Unique identifier for the project</div>
                            <div class="invalid-feedback">
                                Please provide a unique project code.
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="mb-4">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors) && in_array('Location is required', $errors) ? 'is-invalid' : '' ?>"
                                  id="location" 
                                  name="location" 
                                  rows="2"
                                  required
                                  placeholder="Enter complete project location address"><?= htmlspecialchars($formData['location'] ?? '') ?></textarea>
                        <div class="invalid-feedback">
                            Please provide the project location.
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control"
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Optional project description, scope, or notes..."><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                        <div class="form-text">
                            <span id="descriptionCount">0</span>/1000 characters
                        </div>
                    </div>

                    <!-- Project Manager Assignment -->
                    <div class="mb-4">
                        <label for="project_manager_id" class="form-label">Project Manager</label>
                        <select class="form-select" id="project_manager_id" name="project_manager_id">
                            <option value="">Select Project Manager (Optional)</option>
                            <?php foreach ($projectManagers ?? [] as $manager): ?>
                                <option value="<?= $manager['id'] ?>" 
                                        <?= ($formData['project_manager_id'] ?? '') == $manager['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($manager['full_name']) ?> 
                                    (<?= htmlspecialchars($manager['department'] ?? 'N/A') ?>) 
                                    - <?= $manager['managed_projects_count'] ?> projects
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Assign a project manager to oversee this project</div>
                    </div>

                    <!-- Budget -->
                    <div class="mb-4">
                        <label for="budget" class="form-label">Project Budget</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" 
                                   class="form-control"
                                   id="budget" 
                                   name="budget" 
                                   value="<?= htmlspecialchars($formData['budget'] ?? '') ?>"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>
                        <div class="form-text">Optional budget allocation for this project</div>
                    </div>

                    <!-- Date Range -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" 
                                   class="form-control"
                                   id="start_date" 
                                   name="start_date" 
                                   value="<?= htmlspecialchars($formData['start_date'] ?? '') ?>">
                            <div class="form-text">Project commencement date</div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" 
                                   class="form-control"
                                   id="end_date" 
                                   name="end_date" 
                                   value="<?= htmlspecialchars($formData['end_date'] ?? '') ?>">
                            <div class="form-text">Expected completion date</div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   <?= (!isset($formData['is_active']) || $formData['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <strong>Active Project</strong>
                            </label>
                            <div class="form-text">Active projects can have assets assigned and operations performed</div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </button>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" onclick="previewProject()">
                                <i class="bi bi-eye me-1"></i>Preview
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Create Project
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enhanced Sidebar with Help and Guidelines -->
    <div class="col-lg-4">
        <!-- Creation Guide -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Project Creation Guide
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Required Fields</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-check-circle text-success me-1"></i>Project Name</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i>Project Code</li>
                        <li><i class="bi bi-check-circle text-success me-1"></i>Location</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Project Code Guidelines</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-arrow-right me-1"></i>Must be unique</li>
                        <li><i class="bi bi-arrow-right me-1"></i>Use uppercase letters</li>
                        <li><i class="bi bi-arrow-right me-1"></i>Include year/sequence (e.g., PROJ2024001)</li>
                        <li><i class="bi bi-arrow-right me-1"></i>Keep it short and memorable</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6>Best Practices</h6>
                    <ul class="list-unstyled small">
                        <li><i class="bi bi-lightbulb text-warning me-1"></i>Use descriptive project names</li>
                        <li><i class="bi bi-lightbulb text-warning me-1"></i>Include complete location details</li>
                        <li><i class="bi bi-lightbulb text-warning me-1"></i>Set realistic start and end dates</li>
                        <li><i class="bi bi-lightbulb text-warning me-1"></i>Assign a project manager early</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Project Features -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-gear me-2"></i>Project Features
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <p><strong>After creating this project, you can:</strong></p>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-box text-primary me-2"></i>Assign assets to the project</li>
                        <li><i class="bi bi-people text-success me-2"></i>Assign team members</li>
                        <li><i class="bi bi-arrow-down-circle text-info me-2"></i>Manage asset withdrawals</li>
                        <li><i class="bi bi-cart text-warning me-2"></i>Create procurement orders</li>
                        <li><i class="bi bi-clipboard-data text-secondary me-2"></i>Generate project reports</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Tips -->
        <div class="alert alert-info">
            <h6><i class="bi bi-lightbulb me-1"></i>Quick Tips</h6>
            <ul class="small mb-0">
                <li>Use <kbd>Tab</kbd> to navigate between fields</li>
                <li>Project codes are automatically converted to uppercase</li>
                <li>All fields except required ones can be updated later</li>
                <li>Inactive projects won't appear in asset assignment dropdowns</li>
            </ul>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>Project Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    <i class="bi bi-check-circle me-1"></i>Create Project
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-uppercase project code
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Character counter for description
    const descriptionInput = document.getElementById('description');
    const descriptionCount = document.getElementById('descriptionCount');
    if (descriptionInput && descriptionCount) {
        descriptionInput.addEventListener('input', function() {
            descriptionCount.textContent = this.value.length;
        });
        // Initialize counter
        descriptionCount.textContent = descriptionInput.value.length;
    }

    // Date validation
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);
    }

    // Form validation
    const form = document.getElementById('projectForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
});

function validateDates() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const endDateInput = document.getElementById('end_date');
    
    if (startDate && endDate && startDate > endDate) {
        endDateInput.setCustomValidity('End date must be after start date');
        endDateInput.classList.add('is-invalid');
    } else {
        endDateInput.setCustomValidity('');
        endDateInput.classList.remove('is-invalid');
    }
}

function previewProject() {
    const formData = new FormData(document.getElementById('projectForm'));
    const data = Object.fromEntries(formData.entries());
    
    // Build preview content
    let previewHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Project Name:</strong></td><td>${data.name || 'Not specified'}</td></tr>
                    <tr><td><strong>Project Code:</strong></td><td>${data.code || 'Not specified'}</td></tr>
                    <tr><td><strong>Location:</strong></td><td>${data.location || 'Not specified'}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${data.is_active ? 'Active' : 'Inactive'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Additional Details</h6>
                <table class="table table-sm">
                    <tr><td><strong>Project Manager:</strong></td><td>${getSelectedManagerName() || 'Not assigned'}</td></tr>
                    <tr><td><strong>Budget:</strong></td><td>${data.budget ? '₱' + parseFloat(data.budget).toLocaleString() : 'Not specified'}</td></tr>
                    <tr><td><strong>Start Date:</strong></td><td>${data.start_date || 'Not specified'}</td></tr>
                    <tr><td><strong>End Date:</strong></td><td>${data.end_date || 'Not specified'}</td></tr>
                </table>
            </div>
        </div>
    `;
    
    if (data.description) {
        previewHtml += `
            <div class="mt-3">
                <h6>Description</h6>
                <p class="text-muted">${data.description}</p>
            </div>
        `;
    }
    
    document.getElementById('previewContent').innerHTML = previewHtml;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

function getSelectedManagerName() {
    const select = document.getElementById('project_manager_id');
    const selectedOption = select.options[select.selectedIndex];
    return selectedOption.value ? selectedOption.text : null;
}

function submitForm() {
    document.getElementById('projectForm').submit();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 's':
                e.preventDefault();
                document.getElementById('projectForm').submit();
                break;
            case 'p':
                e.preventDefault();
                previewProject();
                break;
        }
    }
});

// Auto-save to localStorage (draft functionality)
function saveDraft() {
    const formData = new FormData(document.getElementById('projectForm'));
    const data = Object.fromEntries(formData.entries());
    localStorage.setItem('project_draft', JSON.stringify(data));
}

function loadDraft() {
    const draft = localStorage.getItem('project_draft');
    if (draft && confirm('Load saved draft?')) {
        const data = JSON.parse(draft);
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[name="${key}"]`);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = data[key] === '1';
                } else {
                    element.value = data[key];
                }
            }
        });
    }
}

// Auto-save every 30 seconds
setInterval(saveDraft, 30000);

// Load draft on page load
window.addEventListener('load', loadDraft);

// Clear draft on successful submission
document.getElementById('projectForm').addEventListener('submit', function() {
    localStorage.removeItem('project_draft');
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Create Project - ConstructLink™';
$pageHeader = 'Create New Project';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Projects', 'url' => '?route=projects'],
    ['title' => 'Create Project', 'url' => '?route=projects/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
