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

        <a href="?route=brands" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Brands
        </a>
    </div>
</div>

<!-- Loading State -->
<div id="loadingState" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 text-muted">Loading brand information...</p>
</div>

<!-- Brand Details -->
<div id="brandDetailsContainer" style="display: none;">
    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Official Brand Name</label>
                                <p class="form-control-plaintext" id="brandName">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Country of Origin</label>
                                <p class="form-control-plaintext" id="brandCountry">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Quality Tier</label>
                                <p class="form-control-plaintext" id="brandTier">-</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Website</label>
                                <p class="form-control-plaintext" id="brandWebsite">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Brand Name Variations</label>
                        <div id="brandVariations" class="form-control-plaintext">-</div>
                    </div>
                </div>
            </div>
            
            <!-- Asset Usage -->
            <div class="card mb-4" id="assetUsageCard">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box-seam me-2"></i>
                        Asset Usage
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h4 class="card-title text-primary mb-1" id="assetCount">0</h4>
                                    <p class="card-text small text-muted mb-0">Total Assets</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h4 class="card-title text-success mb-1" id="activeAssetCount">0</h4>
                                    <p class="card-text small text-muted mb-0">Active Assets</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h4 class="card-title text-warning mb-1" id="recentAssetCount">0</h4>
                                    <p class="card-text small text-muted mb-0">Added This Month</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3" id="assetListSection" style="display: none;">
                        <h6 class="fw-bold">Recent Assets with this Brand</h6>
                        <div class="list-group" id="assetList">
                            <!-- Assets will be populated here -->
                        </div>
                        <div class="mt-2" id="viewAllAssetsBtn" style="display: none;">
                            <a href="#" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-seam me-1"></i>View All Assets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Status Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-flag me-2"></i>
                        Status Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Verification Status</label>
                        <p id="verificationStatus" class="form-control-plaintext">-</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Active Status</label>
                        <p id="activeStatus" class="form-control-plaintext">-</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Created Date</label>
                        <p id="createdDate" class="form-control-plaintext">-</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Last Updated</label>
                        <p id="updatedDate" class="form-control-plaintext">-</p>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?route=brands/edit&id=<?= htmlspecialchars($brandId) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i>Edit Brand Information
                        </a>
                        <button class="btn btn-outline-secondary" onclick="exportBrandData()">
                            <i class="bi bi-download me-1"></i>Export Brand Data
                        </button>
                        <hr>
                        <button class="btn btn-outline-danger" onclick="deleteBrand(<?= htmlspecialchars($brandId) ?>)">
                            <i class="bi bi-trash me-1"></i>Delete Brand
                        </button>
                    </div>
                </div>
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
});

function loadBrandData() {
    const brandId = <?= json_encode($brandId) ?>;
    const loadingState = document.getElementById('loadingState');
    const brandDetailsContainer = document.getElementById('brandDetailsContainer');
    const errorState = document.getElementById('errorState');
    
    fetch(`?route=api/admin/brands&id=${brandId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Brand data loaded:', data);
            
            if (data.success && data.data && data.data.length > 0) {
                const brand = data.data[0]; // Get first result
                populateBrandDetails(brand);
                
                // Show details, hide loading
                loadingState.style.display = 'none';
                brandDetailsContainer.style.display = 'block';
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

function populateBrandDetails(brand) {
    // Basic information
    document.getElementById('brandName').textContent = brand.official_name || '-';
    document.getElementById('brandCountry').textContent = brand.country || 'Not specified';
    
    // Quality tier with badge
    const tierBadge = {
        'premium': '<span class="badge bg-warning text-dark">Premium</span>',
        'mid-range': '<span class="badge bg-info">Mid-range</span>',
        'budget': '<span class="badge bg-secondary">Budget</span>',
        'unknown': '<span class="badge bg-light text-dark">Unknown</span>'
    }[brand.quality_tier] || '<span class="badge bg-light text-dark">Unknown</span>';
    document.getElementById('brandTier').innerHTML = tierBadge;
    
    // Website with link
    if (brand.website) {
        document.getElementById('brandWebsite').innerHTML = `<a href="${brand.website}" target="_blank">${brand.website}</a>`;
    } else {
        document.getElementById('brandWebsite').textContent = 'Not specified';
    }
    
    // Variations
    if (brand.variations && brand.variations.length > 0) {
        const variationsHTML = brand.variations.map(variation => 
            `<span class="badge bg-light text-dark me-1">${variation}</span>`
        ).join('');
        document.getElementById('brandVariations').innerHTML = variationsHTML;
    } else {
        document.getElementById('brandVariations').textContent = 'No variations specified';
    }
    
    // Status information
    document.getElementById('verificationStatus').innerHTML = brand.is_verified ? 
        '<span class="badge bg-success">Verified</span>' : 
        '<span class="badge bg-warning">Unverified</span>';
        
    document.getElementById('activeStatus').innerHTML = brand.is_active ? 
        '<span class="badge bg-success">Active</span>' : 
        '<span class="badge bg-secondary">Inactive</span>';
    
    // Dates
    if (brand.created_at) {
        document.getElementById('createdDate').textContent = new Date(brand.created_at).toLocaleDateString();
    }
    if (brand.updated_at) {
        document.getElementById('updatedDate').textContent = new Date(brand.updated_at).toLocaleDateString();
    } else {
        document.getElementById('updatedDate').textContent = 'Never updated';
    }
    
    // Asset usage
    document.getElementById('assetCount').textContent = brand.assets_count || 0;
    
    // Update delete button state
    const deleteBtn = document.getElementById('deleteBtn');
    if (brand.assets_count > 0) {
        deleteBtn.disabled = true;
        deleteBtn.title = `Cannot delete - brand is used by ${brand.assets_count} asset(s)`;
    }
}

function deleteBrand(id) {
    if (!confirm('Are you sure you want to delete this brand? This action cannot be undone.')) {
        return;
    }
    
    fetch(`?route=api/admin/brands&id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'Brand deleted successfully', 'success');
            
            // Redirect to brands list after a short delay
            setTimeout(() => {
                window.location.href = '?route=brands';
            }, 1500);
        } else {
            showAlert('Failed to delete brand: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting brand:', error);
        showAlert('Failed to delete brand', 'danger');
    });
}

function exportBrandData() {
    showAlert('Export functionality coming soon!', 'info');
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi bi-${getAlertIcon(type)} me-2"></i>
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

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Brand Details - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Brands', 'url' => '?route=brands'],
    ['title' => 'Brand Details', 'url' => '?route=brands/view&id=' . $brandId]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>