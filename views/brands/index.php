<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-award me-2"></i>
        Brand Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-primary me-2" onclick="addBrand()">
            <i class="bi bi-plus-circle me-1"></i>Add Brand
        </button>
        <button class="btn btn-outline-secondary" onclick="loadBrands()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Brand Management
    </h6>
    <p class="mb-0">
        Manage standardized brand names to ensure consistency across all assets. Brands help with procurement matching and asset organization.
    </p>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="brands-search" class="form-label">Search Brands</label>
                    <input type="text" class="form-control" id="brands-search" 
                           placeholder="Search by name, country, or variations...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label for="brands-tier" class="form-label">Quality Tier</label>
                    <select class="form-select" id="brands-tier">
                        <option value="">All Tiers</option>
                        <option value="premium">Premium</option>
                        <option value="standard">Standard</option>
                        <option value="economy">Economy</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-outline-secondary w-100" onclick="loadBrands()">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Brands Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Registered Brands</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Brand Name</th>
                        <th>Variations</th>
                        <th>Country</th>
                        <th>Quality Tier</th>
                        <th>Status</th>
                        <th>Assets</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="brands-list">
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <nav>
            <ul class="pagination justify-content-center" id="brands-pagination">
                <!-- Pagination will be populated by JavaScript -->
            </ul>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadBrands();
    
    // Add search functionality
    const searchInput = document.getElementById('brands-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadBrands();
            }
        });
    }
});

// Load brands with full functionality
function loadBrands(page = 1) {
    const search = document.getElementById('brands-search')?.value || '';
    const tier = document.getElementById('brands-tier')?.value || '';
    const tbody = document.getElementById('brands-list');
    
    // Show loading
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    fetch(`?route=api/admin/brands&page=${page}&search=${encodeURIComponent(search)}&tier=${tier}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBrands(data.data);
                updatePagination(data.pagination, 'brands');
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Failed to load brands: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading brands:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load brands</td></tr>';
        });
}

function displayBrands(brands) {
    const tbody = document.getElementById('brands-list');
    
    if (brands.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No brands found</td></tr>';
        return;
    }
    
    tbody.innerHTML = brands.map(brand => {
        const tierBadge = {
            'premium': 'badge bg-warning text-dark',
            'standard': 'badge bg-info text-white',
            'economy': 'badge bg-secondary'
        }[brand.quality_tier] || 'badge bg-secondary';
        
        const variationsText = brand.variations && brand.variations.length > 0 ? 
            brand.variations.slice(0, 3).join(', ') + (brand.variations.length > 3 ? '...' : '') : 
            '<span class="text-muted">No variations</span>';
        
        return `
            <tr>
                <td><strong>${brand.official_name}</strong></td>
                <td class="small">${variationsText}</td>
                <td>${brand.country || '<span class="text-muted">Not specified</span>'}</td>
                <td><span class="${tierBadge}">${brand.quality_tier.charAt(0).toUpperCase() + brand.quality_tier.slice(1)}</span></td>
                <td>
                    ${brand.is_verified ? 
                        '<span class="badge bg-success">Verified</span>' : 
                        '<span class="badge bg-warning">Unverified</span>'
                    }
                    ${brand.is_active ? 
                        '<span class="badge bg-success ms-1">Active</span>' : 
                        '<span class="badge bg-secondary ms-1">Inactive</span>'
                    }
                </td>
                <td><span class="badge bg-info">${brand.assets_count}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="viewBrand(${brand.id})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-primary" onclick="editBrand(${brand.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        ${brand.assets_count === 0 ? 
                            `<button class="btn btn-outline-danger" onclick="deleteBrand(${brand.id})" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>` : 
                            `<button class="btn btn-outline-secondary" disabled title="Cannot delete - has ${brand.assets_count} assets">
                                <i class="bi bi-trash"></i>
                            </button>`
                        }
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function addBrand() {
    window.location.href = '?route=brands/create';
}

function editBrand(id) {
    window.location.href = '?route=brands/edit&id=' + id;
}

function viewBrand(id) {
    window.location.href = '?route=brands/view&id=' + id;
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
            loadBrands();
            showSuccess(data.message || 'Brand deleted successfully');
        } else {
            showError('Failed to delete brand: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting brand:', error);
        showError('Failed to delete brand');
    });
}


function updatePagination(pagination, prefix) {
    const paginationContainer = document.getElementById(`${prefix}-pagination`);
    if (!paginationContainer || !pagination) return;
    
    let paginationHtml = '';
    
    // Previous button
    if (pagination.has_prev) {
        paginationHtml += `<li class="page-item">
            <button class="page-link" onclick="loadBrands(${pagination.current_page - 1})">Previous</button>
        </li>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        paginationHtml += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <button class="page-link" onclick="loadBrands(${i})">${i}</button>
        </li>`;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHtml += `<li class="page-item">
            <button class="page-link" onclick="loadBrands(${pagination.current_page + 1})">Next</button>
        </li>`;
    }
    
    paginationContainer.innerHTML = paginationHtml;
}

// Utility functions
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
$pageTitle = 'Brand Management - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Brands', 'url' => '?route=brands']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>