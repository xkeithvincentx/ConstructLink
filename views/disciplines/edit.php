<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$disciplineId = $_GET['id'] ?? null;
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<?php if (!$disciplineId): ?>
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No discipline ID provided. Please select a discipline to edit.
</div>
<?php else: ?>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <!-- Loading State -->
        <div class="card" id="loadingCard">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading discipline details...</p>
            </div>
        </div>
        
        <!-- Form Card (initially hidden) -->
        <div class="card d-none" id="formCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>Discipline Information
                </h5>
                <div id="usageInfo" class="d-none">
                    <!-- Usage information will be populated here -->
                </div>
            </div>
            <div class="card-body">
                <form id="disciplineForm" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label">
                                    Discipline Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       placeholder="e.g., CIVIL, ELEC, MECH" maxlength="10" required>
                                <div class="form-text">Unique code for the discipline (max 10 characters)</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active">
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
                            <i class="bi bi-check-circle me-1"></i>Update Discipline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($disciplineId): ?>
    loadDiscipline(<?php echo intval($disciplineId); ?>);
    <?php endif; ?>
    
    const form = document.getElementById('disciplineForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            
            updateDiscipline();
        });
        
        // Real-time validation
        const codeInput = document.getElementById('code');
        const nameInput = document.getElementById('name');
        
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^A-Z0-9_]/gi, '').toUpperCase();
            validateField(this);
        });
        
        nameInput.addEventListener('input', function() {
            validateField(this);
        });
    }
});

function loadDiscipline(disciplineId) {
    // Load discipline data and parent disciplines in parallel
    Promise.all([
        fetch(`?route=api/admin/disciplines&id=${disciplineId}`).then(r => r.json()),
        fetch('?route=api/admin/disciplines&limit=100').then(r => r.json())
    ])
    .then(([disciplineResponse, parentsResponse]) => {
        if (disciplineResponse.success && disciplineResponse.data) {
            const discipline = disciplineResponse.data;
            
            // Populate form fields
            document.getElementById('code').value = discipline.code || '';
            document.getElementById('name').value = discipline.name || '';
            document.getElementById('description').value = discipline.description || '';
            document.getElementById('is_active').checked = discipline.is_active;
            
            // Show usage information if there are assets
            if (discipline.assets_count > 0) {
                const usageInfo = document.getElementById('usageInfo');
                usageInfo.innerHTML = `
                    <span class="badge bg-info">
                        <i class="bi bi-info-circle me-1"></i>
                        ${discipline.assets_count} asset${discipline.assets_count !== 1 ? 's' : ''} using this discipline
                    </span>
                `;
                usageInfo.classList.remove('d-none');
            }
            
            // Load parent options
            if (parentsResponse.success) {
                const parentSelect = document.getElementById('parent_id');
                
                // Clear existing options except the first one
                while (parentSelect.children.length > 1) {
                    parentSelect.removeChild(parentSelect.lastChild);
                }
                
                // Add parent disciplines (exclude current discipline and its children)
                parentsResponse.data
                    .filter(d => d.id !== discipline.id && d.parent_id !== discipline.id)
                    .forEach(d => {
                        const option = document.createElement('option');
                        option.value = d.id;
                        option.textContent = `${d.code} - ${d.name}`;
                        if (d.id === discipline.parent_id) {
                            option.selected = true;
                        }
                        parentSelect.appendChild(option);
                    });
            }
            
            // Hide loading card and show form
            document.getElementById('loadingCard').classList.add('d-none');
            document.getElementById('formCard').classList.remove('d-none');
            
        } else {
            showError('Failed to load discipline: ' + (disciplineResponse.message || 'Unknown error'));
            // Hide loading and show error state
            document.getElementById('loadingCard').innerHTML = `
                <div class="card-body text-center py-5">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-danger">Failed to load discipline</p>
                    <a href="?route=disciplines" class="btn btn-primary">Back to Disciplines</a>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading discipline:', error);
        showError('Failed to load discipline details');
        document.getElementById('loadingCard').innerHTML = `
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                <p class="mt-3 text-danger">Failed to load discipline</p>
                <a href="?route=disciplines" class="btn btn-primary">Back to Disciplines</a>
            </div>
        `;
    });
}

function updateDiscipline() {
    const disciplineId = <?php echo intval($disciplineId ?? 0); ?>;
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-clock me-1"></i>Updating...';
    
    const formData = new FormData(document.getElementById('disciplineForm'));
    const disciplineData = {
        code: formData.get('code'),
        name: formData.get('name'),
        description: formData.get('description'),
        parent_id: formData.get('parent_id') || null,
        is_active: formData.has('is_active')
    };
    
    fetch(`?route=api/admin/disciplines&id=${disciplineId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(disciplineData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Discipline updated successfully');
            // Redirect to disciplines list after a short delay
            setTimeout(() => {
                window.location.href = '?route=disciplines';
            }, 1500);
        } else {
            showError('Failed to update discipline: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error updating discipline:', error);
        showError('Failed to update discipline. Please try again.');
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
$pageTitle = 'Edit Discipline - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Disciplines', 'url' => '?route=disciplines'],
    ['title' => 'Edit', 'url' => '?route=disciplines/edit&id=' . ($disciplineId ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>