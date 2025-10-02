<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-diagram-3 me-2"></i>
        Discipline Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-primary me-2" onclick="addDiscipline()">
            <i class="bi bi-plus-circle me-1"></i>Add Discipline
        </button>
        <button class="btn btn-outline-secondary" onclick="loadDisciplines()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">
        <i class="bi bi-info-circle me-2"></i>Engineering Disciplines
    </h6>
    <p class="mb-0">
        Manage engineering disciplines to categorize assets by their primary use areas. Supports hierarchical organization (e.g., Civil Engineering → Structural, Earthworks).
    </p>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="disciplines-search" class="form-label">Search Disciplines</label>
                    <input type="text" class="form-control" id="disciplines-search" 
                           placeholder="Search by code, name, or description...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Filter by Level</label>
                    <select class="form-select" id="disciplines-level">
                        <option value="">All Levels</option>
                        <option value="parent">Parent Disciplines</option>
                        <option value="child">Sub-Disciplines</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-outline-secondary w-100" onclick="loadDisciplines()">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disciplines Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Engineering Disciplines</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Parent</th>
                        <th>Assets Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="disciplines-list">
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
            <ul class="pagination justify-content-center" id="disciplines-pagination">
                <!-- Pagination will be populated by JavaScript -->
            </ul>
        </nav>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDisciplines();
    
    // Add search functionality
    const searchInput = document.getElementById('disciplines-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadDisciplines();
            }
        });
    }
});

// Load disciplines with full functionality
function loadDisciplines(page = 1) {
    const search = document.getElementById('disciplines-search')?.value || '';
    const tbody = document.getElementById('disciplines-list');
    
    // Show loading
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    fetch(`?route=api/admin/disciplines&page=${page}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDisciplines(data.data);
                updatePagination(data.pagination, 'disciplines');
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Failed to load disciplines: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading disciplines:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load disciplines</td></tr>';
        });
}

function displayDisciplines(disciplines) {
    const tbody = document.getElementById('disciplines-list');
    
    if (disciplines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No disciplines found</td></tr>';
        return;
    }
    
    tbody.innerHTML = disciplines.map(discipline => `
        <tr>
            <td><code class="bg-light px-2 py-1">${discipline.code}</code></td>
            <td><strong>${discipline.name}</strong></td>
            <td class="text-muted">${discipline.description || 'No description'}</td>
            <td>
                ${discipline.parent_name ? 
                    `<span class="badge bg-secondary">${discipline.parent_name}</span>` : 
                    '<span class="text-muted">Root Level</span>'
                }
            </td>
            <td><span class="badge bg-info">${discipline.assets_count}</span></td>
            <td>
                <span class="badge bg-success">Active</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="viewDiscipline(${discipline.id})" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="editDiscipline(${discipline.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    ${discipline.assets_count === 0 ? 
                        `<button class="btn btn-outline-danger" onclick="deleteDiscipline(${discipline.id})" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>` : 
                        `<button class="btn btn-outline-secondary" disabled title="Cannot delete - has ${discipline.assets_count} assets">
                            <i class="bi bi-trash"></i>
                        </button>`
                    }
                </div>
            </td>
        </tr>
    `).join('');
}

function addDiscipline() {
    window.location.href = '?route=disciplines/create';
}

function editDiscipline(id) {
    window.location.href = '?route=disciplines/edit&id=' + id;
}

function viewDiscipline(id) {
    window.location.href = '?route=disciplines/view&id=' + id;
}

function deleteDiscipline(id) {
    if (!confirm('Are you sure you want to delete this discipline? This action cannot be undone.')) {
        return;
    }
    
    fetch(`?route=api/admin/disciplines&id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDisciplines();
            showSuccess(data.message || 'Discipline deleted successfully');
        } else {
            showError('Failed to delete discipline: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting discipline:', error);
        showError('Failed to delete discipline');
    });
}

function updatePagination(pagination, prefix) {
    const paginationContainer = document.getElementById(`${prefix}-pagination`);
    if (!paginationContainer || !pagination) return;
    
    let paginationHtml = '';
    
    // Previous button
    if (pagination.has_prev) {
        paginationHtml += `<li class="page-item">
            <button class="page-link" onclick="loadDisciplines(${pagination.current_page - 1})">Previous</button>
        </li>`;
    }
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        paginationHtml += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
            <button class="page-link" onclick="loadDisciplines(${i})">${i}</button>
        </li>`;
    }
    
    // Next button
    if (pagination.has_next) {
        paginationHtml += `<li class="page-item">
            <button class="page-link" onclick="loadDisciplines(${pagination.current_page + 1})">Next</button>
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
$pageTitle = 'Discipline Management - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Disciplines', 'url' => '?route=disciplines']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>