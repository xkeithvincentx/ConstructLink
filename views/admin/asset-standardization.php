<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$roleConfig = require APP_ROOT . '/config/roles.php';

// Check admin permissions
if (!in_array($user['role_name'], ['System Admin', 'Asset Director'])) {
    http_response_code(403);
    include APP_ROOT . '/views/errors/403.php';
    return;
}

// Load statistics
require_once APP_ROOT . '/core/AssetStandardizer.php';
$standardizer = AssetStandardizer::getInstance();
$stats = $standardizer->getStatistics();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-gear-fill me-2"></i>
        Asset Standardization Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-outline-primary me-2" onclick="refreshCache()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Cache
        </button>
        <a href="?route=admin" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>
</div>

<!-- Statistics Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Spelling Corrections</h6>
                        <h3 class="mb-0"><?= $stats['corrections']['total'] ?? 0 ?></h3>
                        <small class="text-success"><?= $stats['corrections']['approved'] ?? 0 ?> approved</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-spell-check text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Asset Types</h6>
                        <h3 class="mb-0"><?= $stats['types']['total'] ?? 0 ?></h3>
                        <small class="text-info"><?= $stats['types']['categories'] ?? 0 ?> categories</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-collection text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Brands</h6>
                        <h3 class="mb-0"><?= $stats['brands']['total'] ?? 0 ?></h3>
                        <small class="text-warning"><?= $stats['brands']['verified'] ?? 0 ?> verified</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-building text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Monthly Searches</h6>
                        <h3 class="mb-0"><?= $stats['searches']['total_searches'] ?? 0 ?></h3>
                        <small class="text-info"><?= $stats['searches']['corrections_applied'] ?? 0 ?> corrections applied</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-search text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs for different management areas -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="corrections-tab" data-bs-toggle="tab" href="#corrections" role="tab">
            <i class="bi bi-spell-check me-1"></i>Spelling Corrections
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="brands-tab" data-bs-toggle="tab" href="#brands" role="tab">
            <i class="bi bi-building me-1"></i>Brand Management
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="types-tab" data-bs-toggle="tab" href="#types" role="tab">
            <i class="bi bi-collection me-1"></i>Asset Types
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="disciplines-tab" data-bs-toggle="tab" href="#disciplines" role="tab">
            <i class="bi bi-diagram-3 me-1"></i>Disciplines
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="analytics-tab" data-bs-toggle="tab" href="#analytics" role="tab">
            <i class="bi bi-graph-up me-1"></i>Analytics
        </a>
    </li>
</ul>

<div class="tab-content mt-3">
    <!-- Spelling Corrections Tab -->
    <div class="tab-pane fade show active" id="corrections" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Spelling Corrections Management</h5>
                <button class="btn btn-primary btn-sm" onclick="addCorrection()">
                    <i class="bi bi-plus-circle me-1"></i>Add Correction
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="corrections-search" 
                                   placeholder="Search corrections...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="corrections-context">
                                <option value="">All Contexts</option>
                                <option value="tool_name">Tool Names</option>
                                <option value="brand">Brands</option>
                                <option value="material">Materials</option>
                                <option value="category">Categories</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="corrections-status">
                                <option value="">All Status</option>
                                <option value="1">Approved</option>
                                <option value="0">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" onclick="loadCorrections()">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Incorrect</th>
                                <th>Correct</th>
                                <th>Context</th>
                                <th>Confidence</th>
                                <th>Usage Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="corrections-list">
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
                    <ul class="pagination justify-content-center" id="corrections-pagination">
                        <!-- Pagination will be populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Brand Management Tab -->
    <div class="tab-pane fade" id="brands" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Brand Management</h5>
                <button class="btn btn-primary btn-sm" onclick="addBrand()">
                    <i class="bi bi-plus-circle me-1"></i>Add Brand
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="brands-search" 
                                   placeholder="Search brands...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="brands-tier">
                                <option value="">All Quality Tiers</option>
                                <option value="premium">Premium</option>
                                <option value="standard">Standard</option>
                                <option value="economy">Economy</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary w-100" onclick="loadBrands()">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Brand Name</th>
                                <th>Variations</th>
                                <th>Country</th>
                                <th>Quality Tier</th>
                                <th>Verified</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="brands-list">
                            <tr>
                                <td colspan="6" class="text-center">Click Search to load brands</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Asset Types Tab -->
    <div class="tab-pane fade" id="types" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Asset Types Management</h5>
                <button class="btn btn-primary btn-sm" onclick="addAssetType()">
                    <i class="bi bi-plus-circle me-1"></i>Add Asset Type
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Keywords</th>
                                <th>Disciplines</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="types-list">
                            <tr>
                                <td colspan="6" class="text-center">Loading asset types...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Disciplines Tab -->
    <div class="tab-pane fade" id="disciplines" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Disciplines Management</h5>
                <button class="btn btn-primary btn-sm" onclick="addDiscipline()">
                    <i class="bi bi-plus-circle me-1"></i>Add Discipline
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="disciplines-search" 
                                   placeholder="Search disciplines...">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary w-100" onclick="loadDisciplines()">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-info w-100" onclick="loadDisciplines()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Parent</th>
                                <th>Assets Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="disciplines-list">
                            <tr>
                                <td colspan="6" class="text-center">Loading disciplines...</td>
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
    </div>
    
    <!-- Analytics Tab -->
    <div class="tab-pane fade" id="analytics" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Search Analytics & Learning Insights</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Most Common Misspellings (Last 30 Days)</h6>
                                <div id="common-misspellings" class="list-group list-group-flush">
                                    <div class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Search Success Rate</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-success mb-0" id="success-rate">--</h4>
                                            <small class="text-muted">Success Rate</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border-end">
                                            <h4 class="text-warning mb-0" id="correction-rate">--</h4>
                                            <small class="text-muted">Corrections</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info mb-0" id="avg-results">--</h4>
                                        <small class="text-muted">Avg Results</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Top Search Terms (Last 30 Days)</h6>
                                <div id="top-searches" class="row">
                                    <div class="col-12 text-center py-3">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Add Correction Modal -->
<div class="modal fade" id="addCorrectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Spelling Correction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="correctionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="incorrect-text" class="form-label">Incorrect Spelling</label>
                        <input type="text" class="form-control" id="incorrect-text" required>
                    </div>
                    <div class="mb-3">
                        <label for="correct-text" class="form-label">Correct Spelling</label>
                        <input type="text" class="form-control" id="correct-text" required>
                    </div>
                    <div class="mb-3">
                        <label for="correction-context" class="form-label">Context</label>
                        <select class="form-select" id="correction-context" required>
                            <option value="tool_name">Tool Name</option>
                            <option value="brand">Brand</option>
                            <option value="material">Material</option>
                            <option value="category">Category</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="confidence-score" class="form-label">Confidence Score (0.0 - 1.0)</label>
                        <input type="number" class="form-control" id="confidence-score" 
                               min="0" max="1" step="0.01" value="0.8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Correction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let itemsPerPage = 20;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadCorrections();
    loadAnalytics();
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        if (document.getElementById('corrections-tab').classList.contains('active')) {
            loadCorrections();
        }
    }, 300000);
    
    // Add search functionality for disciplines
    const disciplinesSearchInput = document.getElementById('disciplines-search');
    if (disciplinesSearchInput) {
        disciplinesSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadDisciplines();
            }
        });
    }
});

// Load corrections
function loadCorrections(page = 1) {
    const search = document.getElementById('corrections-search').value;
    const context = document.getElementById('corrections-context').value;
    const status = document.getElementById('corrections-status').value;
    
    fetch(`./api/admin/corrections.php?page=${page}&search=${encodeURIComponent(search)}&context=${context}&status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCorrections(data.data);
                updatePagination(data.pagination, 'corrections');
            } else {
                showError('Failed to load corrections: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading corrections:', error);
            showError('Failed to load corrections');
        });
}

// Display corrections in table
function displayCorrections(corrections) {
    const tbody = document.getElementById('corrections-list');
    
    if (corrections.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No corrections found</td></tr>';
        return;
    }
    
    tbody.innerHTML = corrections.map(correction => `
        <tr>
            <td><code>${correction.incorrect}</code></td>
            <td><strong>${correction.correct}</strong></td>
            <td><span class="badge bg-secondary">${correction.context}</span></td>
            <td>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar ${correction.confidence_score >= 0.8 ? 'bg-success' : 'bg-warning'}" 
                         style="width: ${correction.confidence_score * 100}%"></div>
                </div>
                <small>${(correction.confidence_score * 100).toFixed(0)}%</small>
            </td>
            <td><span class="badge bg-info">${correction.usage_count}</span></td>
            <td>
                <span class="badge bg-${correction.approved ? 'success' : 'warning'}">
                    ${correction.approved ? 'Approved' : 'Pending'}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${!correction.approved ? `<button class="btn btn-outline-success" onclick="approveCorrection(${correction.id})">
                        <i class="bi bi-check"></i>
                    </button>` : ''}
                    <button class="btn btn-outline-primary" onclick="editCorrection(${correction.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteCorrection(${correction.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Load analytics data
function loadAnalytics() {
    fetch('./api/admin/analytics.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAnalytics(data.data);
            }
        })
        .catch(error => console.error('Analytics error:', error));
}

// Display analytics
function displayAnalytics(analytics) {
    // Update success metrics
    if (analytics.metrics) {
        document.getElementById('success-rate').textContent = analytics.metrics.success_rate + '%';
        document.getElementById('correction-rate').textContent = analytics.metrics.correction_rate + '%';
        document.getElementById('avg-results').textContent = analytics.metrics.avg_results;
    }
    
    // Display common misspellings
    if (analytics.misspellings) {
        const container = document.getElementById('common-misspellings');
        container.innerHTML = analytics.misspellings.map(item => `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <code>${item.incorrect}</code> → <strong>${item.correct}</strong>
                </div>
                <span class="badge bg-primary">${item.count}</span>
            </div>
        `).join('');
    }
    
    // Display top searches
    if (analytics.searches) {
        const container = document.getElementById('top-searches');
        container.innerHTML = analytics.searches.map((search, index) => `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between">
                    <span>${index + 1}. ${search.query}</span>
                    <span class="text-muted">${search.count}</span>
                </div>
            </div>
        `).join('');
    }
}

// Add correction
function addCorrection() {
    const modal = new bootstrap.Modal(document.getElementById('addCorrectionModal'));
    modal.show();
    
    document.getElementById('correctionForm').onsubmit = function(e) {
        e.preventDefault();
        
        const formData = {
            incorrect: document.getElementById('incorrect-text').value,
            correct: document.getElementById('correct-text').value,
            context: document.getElementById('correction-context').value,
            confidence_score: document.getElementById('confidence-score').value
        };
        
        fetch('./api/admin/corrections.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                loadCorrections();
                showSuccess('Correction added successfully');
                document.getElementById('correctionForm').reset();
            } else {
                showError('Failed to add correction: ' + data.message);
            }
        })
        .catch(error => {
            showError('Failed to add correction');
            console.error(error);
        });
    };
}

// Approve correction
function approveCorrection(id) {
    if (!confirm('Approve this correction?')) return;
    
    fetch(`./api/admin/corrections.php?id=${id}&action=approve`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCorrections();
            showSuccess('Correction approved');
        } else {
            showError('Failed to approve correction');
        }
    })
    .catch(error => {
        showError('Failed to approve correction');
        console.error(error);
    });
}

// Delete correction
function deleteCorrection(id) {
    if (!confirm('Delete this correction? This action cannot be undone.')) return;
    
    fetch(`./api/admin/corrections.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCorrections();
            showSuccess('Correction deleted');
        } else {
            showError('Failed to delete correction');
        }
    })
    .catch(error => {
        showError('Failed to delete correction');
        console.error(error);
    });
}

// Refresh cache
function refreshCache() {
    fetch('./api/admin/cache.php?action=refresh', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Cache refreshed successfully');
        } else {
            showError('Failed to refresh cache');
        }
    })
    .catch(error => {
        showError('Failed to refresh cache');
        console.error(error);
    });
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
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function updatePagination(pagination, prefix) {
    // Implementation for pagination updates
    const paginationContainer = document.getElementById(`${prefix}-pagination`);
    // Add pagination logic here
}

// Tab switching handlers
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('href').substring(1);
        
        switch(target) {
            case 'brands':
                loadBrands();
                break;
            case 'types':
                loadAssetTypes();
                break;
            case 'disciplines':
                loadDisciplines();
                break;
            case 'analytics':
                loadAnalytics();
                break;
        }
    });
});

// Load disciplines with full functionality
function loadDisciplines(page = 1) {
    const search = document.getElementById('disciplines-search')?.value || '';
    const tbody = document.getElementById('disciplines-list');
    
    // Show loading
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    fetch(`./api/admin/disciplines.php?page=${page}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDisciplines(data.data);
                updatePagination(data.pagination, 'disciplines');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to load disciplines: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading disciplines:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load disciplines</td></tr>';
        });
}

function displayDisciplines(disciplines) {
    const tbody = document.getElementById('disciplines-list');
    
    if (disciplines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No disciplines found</td></tr>';
        return;
    }
    
    tbody.innerHTML = disciplines.map(discipline => `
        <tr>
            <td><code>${discipline.code}</code></td>
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
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editDiscipline(${discipline.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    ${discipline.assets_count === 0 ? 
                        `<button class="btn btn-outline-danger" onclick="deleteDiscipline(${discipline.id})" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>` : 
                        `<button class="btn btn-outline-secondary" disabled title="Cannot delete - has assets">
                            <i class="bi bi-trash"></i>
                        </button>`
                    }
                </div>
            </td>
        </tr>
    `).join('');
}

function addDiscipline() {
    showDisciplineModal();
}

function editDiscipline(id) {
    // Find the discipline data from the current table
    const rows = document.querySelectorAll('#disciplines-list tr');
    let discipline = null;
    
    for (let row of rows) {
        const editBtn = row.querySelector(`button[onclick="editDiscipline(${id})"]`);
        if (editBtn) {
            const cells = row.querySelectorAll('td');
            discipline = {
                id: id,
                code: cells[0].querySelector('code').textContent,
                name: cells[1].querySelector('strong').textContent,
                description: cells[2].textContent === 'No description' ? '' : cells[2].textContent
            };
            break;
        }
    }
    
    if (discipline) {
        showDisciplineModal(discipline);
    }
}

function deleteDiscipline(id) {
    if (!confirm('Are you sure you want to delete this discipline? This action cannot be undone.')) {
        return;
    }
    
    fetch(`./api/admin/disciplines.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDisciplines();
            showSuccess('Discipline deleted successfully');
        } else {
            showError('Failed to delete discipline: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting discipline:', error);
        showError('Failed to delete discipline');
    });
}

function showDisciplineModal(discipline = null) {
    const isEdit = discipline !== null;
    const modalHtml = `
        <div class="modal fade" id="disciplineModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${isEdit ? 'Edit' : 'Add'} Discipline</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="disciplineForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="discipline-code" class="form-label">Code *</label>
                                <input type="text" class="form-control" id="discipline-code" 
                                       value="${discipline?.code || ''}" required 
                                       pattern="[A-Z0-9_]+" title="Use uppercase letters, numbers, and underscores only">
                                <div class="form-text">Use uppercase letters, numbers, and underscores (e.g., CIVIL_ENG, ELEC)</div>
                            </div>
                            <div class="mb-3">
                                <label for="discipline-name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="discipline-name" 
                                       value="${discipline?.name || ''}" required>
                            </div>
                            <div class="mb-3">
                                <label for="discipline-description" class="form-label">Description</label>
                                <textarea class="form-control" id="discipline-description" rows="3">${discipline?.description || ''}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="discipline-parent" class="form-label">Parent Discipline</label>
                                <select class="form-select" id="discipline-parent">
                                    <option value="">Root Level (No Parent)</option>
                                </select>
                                <div class="form-text">Optional: Select a parent discipline to create a hierarchy</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">${isEdit ? 'Update' : 'Create'} Discipline</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    const existingModal = document.getElementById('disciplineModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add new modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Load parent options
    loadParentDisciplines(discipline?.parent_id);
    
    // Initialize modal
    const modal = new bootstrap.Modal(document.getElementById('disciplineModal'));
    modal.show();
    
    // Handle form submission
    document.getElementById('disciplineForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveDiscipline(discipline?.id, modal);
    });
    
    // Cleanup modal on hide
    document.getElementById('disciplineModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function loadParentDisciplines(selectedParentId = null) {
    const select = document.getElementById('discipline-parent');
    
    fetch('./api/admin/disciplines.php?limit=100')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Only show root level disciplines as potential parents
                const rootDisciplines = data.data.filter(d => !d.parent_id);
                
                select.innerHTML = '<option value="">Root Level (No Parent)</option>';
                rootDisciplines.forEach(discipline => {
                    const option = document.createElement('option');
                    option.value = discipline.id;
                    option.textContent = `${discipline.code} - ${discipline.name}`;
                    if (selectedParentId && discipline.id === selectedParentId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading parent disciplines:', error);
        });
}

function saveDiscipline(id, modal) {
    const formData = {
        code: document.getElementById('discipline-code').value.trim().toUpperCase(),
        name: document.getElementById('discipline-name').value.trim(),
        description: document.getElementById('discipline-description').value.trim(),
        parent_id: document.getElementById('discipline-parent').value || null
    };
    
    const method = id ? 'PUT' : 'POST';
    const url = id ? `./api/admin/disciplines.php?id=${id}` : './api/admin/disciplines.php';
    
    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            loadDisciplines();
            showSuccess(data.message || `Discipline ${id ? 'updated' : 'created'} successfully`);
        } else {
            showError('Failed to save discipline: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving discipline:', error);
        showError('Failed to save discipline');
    });
}

// Brand Management Functions
function loadBrands(page = 1) {
    const search = document.getElementById('brands-search')?.value || '';
    const tier = document.getElementById('brands-tier')?.value || '';
    const tbody = document.getElementById('brands-list');
    
    // Show loading
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    fetch(`./api/admin/brands.php?page=${page}&search=${encodeURIComponent(search)}&tier=${tier}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBrands(data.data);
                updatePagination(data.pagination, 'brands');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to load brands: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading brands:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load brands</td></tr>';
        });
}

function displayBrands(brands) {
    const tbody = document.getElementById('brands-list');
    
    if (brands.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No brands found</td></tr>';
        return;
    }
    
    tbody.innerHTML = brands.map(brand => {
        const tierBadge = {
            'premium': 'badge bg-warning',
            'standard': 'badge bg-info',
            'economy': 'badge bg-secondary'
        }[brand.quality_tier] || 'badge bg-secondary';
        
        const variationsText = brand.variations && brand.variations.length > 0 ? 
            brand.variations.slice(0, 3).join(', ') + (brand.variations.length > 3 ? '...' : '') : 
            'No variations';
        
        return `
            <tr>
                <td><strong>${brand.official_name}</strong></td>
                <td class="text-muted small">${variationsText}</td>
                <td>${brand.country || 'Not specified'}</td>
                <td><span class="${tierBadge}">${brand.quality_tier}</span></td>
                <td>
                    ${brand.is_verified ? 
                        '<span class="badge bg-success">Verified</span>' : 
                        '<span class="badge bg-warning">Unverified</span>'
                    }
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
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
    showBrandModal();
}

function editBrand(id) {
    // Fetch brand data first
    fetch(`./api/admin/brands.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                showBrandModal(data.data);
            }
        })
        .catch(error => {
            console.error('Error fetching brand:', error);
            showError('Failed to load brand data');
        });
}

function showBrandModal(brand = null) {
    const isEdit = brand !== null;
    const modalHtml = `
        <div class="modal fade" id="brandModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${isEdit ? 'Edit' : 'Add'} Brand</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="brandForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="brand-name" class="form-label">Official Name *</label>
                                <input type="text" class="form-control" id="brand-name" 
                                       value="${brand?.official_name || ''}" required>
                                <div class="form-text">The official brand name (e.g., DeWalt, Makita)</div>
                            </div>
                            <div class="mb-3">
                                <label for="brand-variations" class="form-label">Variations</label>
                                <textarea class="form-control" id="brand-variations" rows="2" 
                                          placeholder="Enter variations separated by commas">${brand?.variations?.join(', ') || ''}</textarea>
                                <div class="form-text">Common misspellings or variations (e.g., "De Walt, Dewalt, DW")</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="brand-country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="brand-country" 
                                               value="${brand?.country || ''}" placeholder="e.g., USA, Japan">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="brand-tier" class="form-label">Quality Tier</label>
                                        <select class="form-select" id="brand-tier">
                                            <option value="standard" ${brand?.quality_tier === 'standard' ? 'selected' : ''}>Standard</option>
                                            <option value="premium" ${brand?.quality_tier === 'premium' ? 'selected' : ''}>Premium</option>
                                            <option value="economy" ${brand?.quality_tier === 'economy' ? 'selected' : ''}>Economy</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="brand-verified" 
                                       ${brand?.is_verified ? 'checked' : ''}>
                                <label class="form-check-label" for="brand-verified">
                                    Mark as verified brand
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">${isEdit ? 'Update' : 'Create'} Brand</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    const existingModal = document.getElementById('brandModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add new modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Initialize modal
    const modal = new bootstrap.Modal(document.getElementById('brandModal'));
    modal.show();
    
    // Handle form submission
    document.getElementById('brandForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveBrand(brand?.id, modal);
    });
    
    // Cleanup modal on hide
    document.getElementById('brandModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function saveBrand(id, modal) {
    const variations = document.getElementById('brand-variations').value
        .split(',')
        .map(v => v.trim())
        .filter(v => v.length > 0);
    
    const formData = {
        official_name: document.getElementById('brand-name').value.trim(),
        variations: variations,
        country: document.getElementById('brand-country').value.trim(),
        quality_tier: document.getElementById('brand-tier').value,
        is_verified: document.getElementById('brand-verified').checked
    };
    
    const method = id ? 'PUT' : 'POST';
    const url = id ? `./api/admin/brands.php?id=${id}` : './api/admin/brands.php';
    
    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            loadBrands();
            showSuccess(data.message || `Brand ${id ? 'updated' : 'created'} successfully`);
        } else {
            showError('Failed to save brand: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving brand:', error);
        showError('Failed to save brand');
    });
}

function deleteBrand(id) {
    if (!confirm('Are you sure you want to delete this brand? This action cannot be undone.')) {
        return;
    }
    
    fetch(`./api/admin/brands.php?id=${id}`, {
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

// Asset Types Management
function loadAssetTypes() {
    document.getElementById('types-list').innerHTML = '<tr><td colspan="6" class="text-center">Asset types management coming soon...</td></tr>';
}

function addAssetType() {
    showInfo('Asset type management will be available in the next update');
}

function showInfo(message) {
    showAlert(message, 'info');
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Include main layout
$pageTitle = 'Asset Standardization Management - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Admin', 'url' => '?route=admin'],
    ['title' => 'Asset Standardization', 'url' => '?route=admin/asset-standardization']
];

include APP_ROOT . '/views/layouts/main.php';
?>