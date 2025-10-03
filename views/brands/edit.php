<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();

// Get brand ID from URL
$brandId = $_GET['id'] ?? null;
if (!$brandId) {
    header('Location: ?route=brands');
    exit;
}
?>

<!-- Navigation Actions (No Header - handled by layout) -->
<!-- Add navigation buttons here if needed -->

<!-- Loading State -->
<div id="loadingState" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 text-muted">Loading brand information...</p>
</div>

<!-- Edit Brand Form -->
<div id="editFormContainer" class="row justify-content-center" style="display: none;">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil me-2"></i>
                    Edit Brand Information
                </h5>
            </div>
            <div class="card-body">
                <form id="brandForm" novalidate>
                    <input type="hidden" id="brand_id" name="brand_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="official_name" class="form-label">
                                    Official Brand Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="official_name" name="official_name" required>
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
                                <input type="text" class="form-control" id="country" name="country">
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
                                <input type="url" class="form-control" id="website" name="website">
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
                        <textarea class="form-control" id="variations" name="variations" rows="3"></textarea>
                        <div class="form-text">
                            Different ways this brand name might appear. Separate each variation with a comma.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_verified" name="is_verified">
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
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active">
                                <label class="form-check-label" for="is_active">
                                    Active Brand
                                </label>
                                <div class="form-text">
                                    Active brands are available for selection in forms
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Asset Usage Information -->
                    <div class="alert alert-info" id="assetUsageInfo" style="display: none;">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Asset Usage
                        </h6>
                        <p class="mb-0" id="assetUsageText"></p>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="?route=brands" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            Update Brand
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Error State -->
<div id="errorState" class="text-center py-5" style="display: none;">
    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
    <h4 class="mt-3">Error Loading Brand</h4>
    <p class="text-muted" id="errorMessage">Failed to load brand information.</p>
    <a href="?route=brands" class="btn btn-primary">Back to Brands</a>
</div>

<!-- Alert Container -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadBrandData();
    
    const form = document.getElementById('brandForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        updateBrand();
    });
});

function loadBrandData() {
    const brandId = <?= json_encode($brandId) ?>;
    const loadingState = document.getElementById('loadingState');
    const editFormContainer = document.getElementById('editFormContainer');
    const errorState = document.getElementById('errorState');
    
    fetch(`?route=api/admin/brands&id=${brandId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Brand data loaded:', data);
            
            if (data.success && data.data && data.data.length > 0) {
                const brand = data.data[0]; // Get first result
                populateForm(brand);
                
                // Show form, hide loading
                loadingState.style.display = 'none';
                editFormContainer.style.display = 'block';
            } else {
                throw new Error(data.message || 'Brand not found');
            }
        })
        .catch(error => {
            console.error('Error loading brand:', error);
            
            // Show error state
            loadingState.style.display = 'none';
            errorState.style.display = 'block';
            document.getElementById('errorMessage').textContent = error.message || 'Failed to load brand information';
        });
}

function populateForm(brand) {
    // Populate form fields
    document.getElementById('brand_id').value = brand.id;
    document.getElementById('official_name').value = brand.official_name || '';
    document.getElementById('country').value = brand.country || '';
    document.getElementById('quality_tier').value = brand.quality_tier || 'unknown';
    document.getElementById('website').value = brand.website || '';
    document.getElementById('is_verified').checked = brand.is_verified || false;
    document.getElementById('is_active').checked = brand.is_active || false;
    
    // Handle variations
    if (brand.variations && Array.isArray(brand.variations)) {
        document.getElementById('variations').value = brand.variations.join(', ');
    }
    
    // Show asset usage info
    if (brand.assets_count > 0) {
        const assetUsageInfo = document.getElementById('assetUsageInfo');
        const assetUsageText = document.getElementById('assetUsageText');
        assetUsageText.textContent = `This brand is currently used by ${brand.assets_count} asset(s). Changes will affect how these assets are displayed.`;
        assetUsageInfo.style.display = 'block';
    }
}

function updateBrand() {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    const buttonText = submitBtn.querySelector('span:not(.spinner-border)') || submitBtn.childNodes[submitBtn.childNodes.length - 1];
    
    // Show loading state
    spinner.classList.remove('d-none');
    submitBtn.disabled = true;
    if (buttonText) {
        buttonText.textContent = ' Updating...';
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
    
    const brandId = formData.get('brand_id');
    
    console.log('Updating brand:', brandId, brandData);
    
    fetch(`?route=api/admin/brands&id=${brandId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(brandData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('API response:', data);
        
        if (data.success) {
            showAlert('Brand updated successfully!', 'success');
            
            // Redirect to brands list after a short delay
            setTimeout(() => {
                window.location.href = '?route=brands';
            }, 1500);
        } else {
            showAlert('Error: ' + (data.message || 'Failed to update brand'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating brand:', error);
        showAlert('Error: Failed to update brand. Please try again.', 'danger');
    })
    .finally(() => {
        // Reset loading state
        spinner.classList.add('d-none');
        submitBtn.disabled = false;
        if (buttonText) {
            buttonText.textContent = ' Update Brand';
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
$pageTitle = 'Edit Brand - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Brands', 'url' => '?route=brands'],
    ['title' => 'Edit Brand', 'url' => '?route=brands/edit&id=' . $brandId]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>