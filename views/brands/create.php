<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Add Brand Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Brand Information
                </h5>
            </div>
            <div class="card-body">
                <form id="brandForm" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="official_name" class="form-label">
                                    Official Brand Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="official_name" name="official_name" required 
                                       placeholder="e.g., DeWalt, Makita, Bosch">
                                <div class="invalid-feedback">
                                    Please provide a valid brand name.
                                </div>
                                <div class="form-text">
                                    The official, standardized name of the brand
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country of Origin</label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       placeholder="e.g., USA, Japan, Germany">
                                <div class="form-text">
                                    Where the brand originates from
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quality_tier" class="form-label">Quality Tier</label>
                                <select class="form-select" id="quality_tier" name="quality_tier">
                                    <option value="unknown">Unknown</option>
                                    <option value="premium">Premium</option>
                                    <option value="mid-range">Mid-range</option>
                                    <option value="budget">Budget</option>
                                </select>
                                <div class="form-text">
                                    General quality and price category
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" 
                                       placeholder="https://www.brandname.com">
                                <div class="invalid-feedback">
                                    Please provide a valid URL.
                                </div>
                                <div class="form-text">
                                    Official brand website (optional)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="variations" class="form-label">Brand Name Variations</label>
                        <textarea class="form-control" id="variations" name="variations" rows="3" 
                                  placeholder="Enter variations separated by commas, e.g.: DEWALT, De Walt, DeWalt Tools"></textarea>
                        <div class="form-text">
                            Different ways this brand name might appear on products or in documents. Separate each variation with a comma.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified" checked>
                                <label class="form-check-label" for="is_verified">
                                    Verified Brand
                                </label>
                                <div class="form-text">
                                    Mark as verified if this is an official/confirmed brand
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active Brand
                                </label>
                                <div class="form-text">
                                    Active brands are available for selection in forms
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="?route=brands" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            Create Brand
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('brandForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        submitBrand();
    });
});

function submitBrand() {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    const buttonText = submitBtn.querySelector('span:not(.spinner-border)') || submitBtn.childNodes[submitBtn.childNodes.length - 1];
    
    // Show loading state
    spinner.classList.remove('d-none');
    submitBtn.disabled = true;
    if (buttonText) {
        buttonText.textContent = ' Creating...';
    }
    
    // Gather form data
    const formData = new FormData(document.getElementById('brandForm'));
    
    // Process variations
    const variationsText = formData.get('variations') || '';
    const variations = variationsText ? variationsText.split(',').map(v => v.trim()).filter(v => v) : [];
    
    const brandData = {
        official_name: formData.get('official_name'),
        country: formData.get('country'),
        website: formData.get('website'),
        quality_tier: formData.get('quality_tier'),
        variations: variations,
        is_verified: formData.get('is_verified') === 'on',
        is_active: formData.get('is_active') === 'on'
    };
    
    console.log('Submitting brand data:', brandData);
    
    fetch('?route=api/admin/brands', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(brandData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('API response:', data);
        
        if (data.success) {
            showAlert('Brand created successfully!', 'success');
            
            // Redirect to brands list after a short delay
            setTimeout(() => {
                window.location.href = '?route=brands';
            }, 1500);
        } else {
            showAlert('Error: ' + (data.message || 'Failed to create brand'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error creating brand:', error);
        showAlert('Error: Failed to create brand. Please try again.', 'danger');
    })
    .finally(() => {
        // Reset loading state
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
        if (buttonText) {
            buttonText.textContent = ' Create Brand';
        }
    });
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
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
$pageTitle = 'Add Brand - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Brands', 'url' => '?route=brands'],
    ['title' => 'Add Brand', 'url' => '?route=brands/create']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>