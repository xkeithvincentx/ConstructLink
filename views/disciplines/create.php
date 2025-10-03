<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>Discipline Information
                </h5>
            </div>
            <div class="card-body">
                <form id="disciplineForm" novalidate>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="code" class="form-label">
                                    Discipline Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       placeholder="e.g., CIVIL, ELEC, MECH" maxlength="10" required>
                                <div class="form-text">Internal system code (max 10 characters)</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="iso_code" class="form-label">
                                    ISO Code
                                </label>
                                <input type="text" class="form-control" id="iso_code" name="iso_code" 
                                       placeholder="e.g., CV, EL, ME" maxlength="2">
                                <div class="form-text">2-char ISO 55000:2024 code for asset references</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Discipline Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="e.g., Civil Engineering" maxlength="50" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Brief description of the discipline and its scope"></textarea>
                        <div class="form-text">Describe the scope and purpose of this discipline</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Discipline</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Root Level (No Parent)</option>
                        </select>
                        <div class="form-text">Select a parent discipline to create a hierarchical structure</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active Discipline
                            </label>
                            <div class="form-text">Inactive disciplines won't be available for asset assignment</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="?route=disciplines" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>Create Discipline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadParentDisciplines();
    
    const form = document.getElementById('disciplineForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        submitDiscipline();
    });
    
    // Real-time validation
    const codeInput = document.getElementById('code');
    const isoCodeInput = document.getElementById('iso_code');
    const nameInput = document.getElementById('name');
    
    codeInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^A-Z0-9_]/gi, '').toUpperCase();
        validateField(this);
    });
    
    isoCodeInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^A-Z]/gi, '').toUpperCase();
        if (this.value.length > 2) {
            this.value = this.value.substring(0, 2);
        }
        validateField(this);
    });
    
    nameInput.addEventListener('input', function() {
        validateField(this);
    });
});

function loadParentDisciplines() {
    fetch('?route=api/admin/disciplines&limit=100')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const parentSelect = document.getElementById('parent_id');
                
                // Clear existing options except the first one
                while (parentSelect.children.length > 1) {
                    parentSelect.removeChild(parentSelect.lastChild);
                }
                
                // Add parent disciplines (only root level ones)
                data.data.filter(discipline => !discipline.parent_id).forEach(discipline => {
                    const option = document.createElement('option');
                    option.value = discipline.id;
                    option.textContent = `${discipline.code} - ${discipline.name}`;
                    parentSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading parent disciplines:', error);
        });
}

function submitDiscipline() {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-clock me-1"></i>Creating...';
    
    const formData = new FormData(document.getElementById('disciplineForm'));
    const disciplineData = {
        code: formData.get('code'),
        iso_code: formData.get('iso_code') || null,
        name: formData.get('name'),
        description: formData.get('description'),
        parent_id: formData.get('parent_id') || null,
        is_active: formData.has('is_active')
    };
    
    fetch('?route=api/admin/disciplines', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(disciplineData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Discipline created successfully');
            // Redirect to disciplines list after a short delay
            setTimeout(() => {
                window.location.href = '?route=disciplines';
            }, 1500);
        } else {
            showError('Failed to create discipline: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error creating discipline:', error);
        showError('Failed to create discipline. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function validateField(field) {
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    
    if (field.checkValidity()) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        if (feedback) feedback.textContent = '';
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        if (feedback) {
            if (field.validity.valueMissing) {
                feedback.textContent = `${field.labels[0].textContent.replace(' *', '')} is required`;
            } else if (field.validity.tooLong) {
                feedback.textContent = `${field.labels[0].textContent} is too long`;
            }
        }
    }
}

function showSuccess(message) {
    showAlert(message, 'success');
}

function showError(message) {
    showAlert(message, 'danger');
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 1050;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Add New Discipline - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Disciplines', 'url' => '?route=disciplines'],
    ['title' => 'Add New', 'url' => '?route=disciplines/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>