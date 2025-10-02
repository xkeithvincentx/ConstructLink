<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$disciplineId = $_GET['id'] ?? null;
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-eye me-2"></i>
        Discipline Details
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?route=disciplines" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Back to Disciplines
        </a>
        <?php if ($disciplineId): ?>
        <div class="btn-group">
            <a href="?route=disciplines/edit&id=<?php echo htmlspecialchars($disciplineId); ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            <button class="btn btn-outline-danger" id="deleteBtn" data-discipline-id="<?php echo htmlspecialchars($disciplineId); ?>">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$disciplineId): ?>
<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No discipline ID provided. Please select a discipline to view.
</div>
<?php else: ?>

<!-- Loading State -->
<div class="card" id="loadingCard">
    <div class="card-body text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading discipline details...</p>
    </div>
</div>

<!-- Content (initially hidden) -->
<div class="d-none" id="contentArea">
    <div class="row">
        <!-- Main Information -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-diagram-3 me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"><strong>Code:</strong></div>
                        <div class="col-sm-9">
                            <code class="bg-light px-2 py-1" id="disciplineCode">-</code>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-sm-3"><strong>Name:</strong></div>
                        <div class="col-sm-9" id="disciplineName">-</div>
                    </div>
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-sm-3"><strong>Description:</strong></div>
                        <div class="col-sm-9" id="disciplineDescription">-</div>
                    </div>
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-sm-3"><strong>Parent Discipline:</strong></div>
                        <div class="col-sm-9" id="disciplineParent">-</div>
                    </div>
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-sm-3"><strong>Created:</strong></div>
                        <div class="col-sm-9" id="disciplineCreated">-</div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Usage Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                    <i class="bi bi-boxes text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0" id="assetsCount">0</h5>
                                    <small class="text-muted">Assets Associated</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="childrenContainer" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                    <i class="bi bi-diagram-2 text-info fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0" id="childrenCount">0</h5>
                                    <small class="text-muted">Sub-disciplines</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub-disciplines (if any) -->
            <div class="card mt-4 d-none" id="childrenCard">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-diagram-2 me-2"></i>Sub-disciplines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Assets</th>
                                </tr>
                            </thead>
                            <tbody id="childrenList">
                                <!-- Sub-disciplines will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Status Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div id="disciplineStatus">
                            <span class="badge bg-secondary">Loading...</span>
                        </div>
                    </div>

                    <div class="mb-3" id="hierarchyInfo">
                        <label class="form-label">Hierarchy Level</label>
                        <div id="hierarchyLevel">
                            <span class="badge bg-secondary">Loading...</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <div id="sortOrder">-</div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2"></i>Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="?route=disciplines/edit&id=<?php echo htmlspecialchars($disciplineId); ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i>Edit Discipline
                        </a>
                        <button class="btn btn-outline-danger" id="deleteActionBtn" data-discipline-id="<?php echo htmlspecialchars($disciplineId); ?>">
                            <i class="bi bi-trash me-1"></i>Delete Discipline
                        </button>
                        <a href="?route=disciplines" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                </div>
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
    
    // Setup delete button handlers
    document.querySelectorAll('#deleteBtn, #deleteActionBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const disciplineId = this.getAttribute('data-discipline-id');
            if (disciplineId) {
                deleteDiscipline(disciplineId);
            }
        });
    });
});

function loadDiscipline(disciplineId) {
    // Load discipline data and all disciplines for hierarchy info
    Promise.all([
        fetch(`?route=api/admin/disciplines&id=${disciplineId}`).then(r => r.json()),
        fetch('?route=api/admin/disciplines&limit=100').then(r => r.json())
    ])
    .then(([disciplineResponse, allDisciplinesResponse]) => {
        if (disciplineResponse.success && disciplineResponse.data) {
            const discipline = disciplineResponse.data;
            
            // Populate basic information
            document.getElementById('disciplineCode').textContent = discipline.code || '-';
            document.getElementById('disciplineName').textContent = discipline.name || '-';
            document.getElementById('disciplineDescription').textContent = discipline.description || 'No description provided';
            
            // Parent discipline
            if (discipline.parent_name) {
                document.getElementById('disciplineParent').innerHTML = `
                    <span class="badge bg-secondary">${discipline.parent_name}</span>
                `;
            } else {
                document.getElementById('disciplineParent').innerHTML = '<span class="text-muted">Root Level</span>';
            }
            
            // Created date
            if (discipline.created_at) {
                const createdDate = new Date(discipline.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('disciplineCreated').textContent = createdDate;
            }
            
            // Status
            const statusBadge = discipline.is_active ? 
                '<span class="badge bg-success">Active</span>' : 
                '<span class="badge bg-danger">Inactive</span>';
            document.getElementById('disciplineStatus').innerHTML = statusBadge;
            
            // Hierarchy level
            const hierarchyBadge = discipline.parent_id ? 
                '<span class="badge bg-info">Sub-discipline</span>' : 
                '<span class="badge bg-primary">Parent Discipline</span>';
            document.getElementById('hierarchyLevel').innerHTML = hierarchyBadge;
            
            // Sort order
            document.getElementById('sortOrder').textContent = discipline.sort_order || '0';
            
            // Assets count
            document.getElementById('assetsCount').textContent = discipline.assets_count || '0';
            
            // Find and display sub-disciplines
            if (allDisciplinesResponse.success) {
                const children = allDisciplinesResponse.data.filter(d => d.parent_id === discipline.id);
                
                if (children.length > 0) {
                    document.getElementById('childrenCount').textContent = children.length;
                    document.getElementById('childrenContainer').style.display = 'block';
                    
                    const childrenHtml = children.map(child => `
                        <tr>
                            <td><code class="bg-light px-2 py-1">${child.code}</code></td>
                            <td><strong>${child.name}</strong></td>
                            <td class="text-muted">${child.description || 'No description'}</td>
                            <td>
                                ${child.is_active ? 
                                    '<span class="badge bg-success">Active</span>' : 
                                    '<span class="badge bg-danger">Inactive</span>'
                                }
                            </td>
                            <td><span class="badge bg-info">${child.assets_count || 0}</span></td>
                        </tr>
                    `).join('');
                    
                    document.getElementById('childrenList').innerHTML = childrenHtml;
                    document.getElementById('childrenCard').classList.remove('d-none');
                }
            }
            
            // Update delete button states based on usage
            const hasAssets = discipline.assets_count > 0;
            const hasChildren = allDisciplinesResponse.success && 
                allDisciplinesResponse.data.some(d => d.parent_id === discipline.id);
            
            const deleteButtons = document.querySelectorAll('#deleteBtn, #deleteActionBtn');
            deleteButtons.forEach(btn => {
                if (hasAssets || hasChildren) {
                    btn.disabled = true;
                    btn.title = hasAssets ? 
                        `Cannot delete - has ${discipline.assets_count} associated assets` :
                        'Cannot delete - has sub-disciplines';
                    btn.innerHTML = btn.innerHTML.replace('Delete', 'Cannot Delete');
                } else {
                    btn.disabled = false;
                    btn.title = 'Delete this discipline';
                }
            });
            
            // Hide loading and show content
            document.getElementById('loadingCard').classList.add('d-none');
            document.getElementById('contentArea').classList.remove('d-none');
            
        } else {
            showError('Failed to load discipline: ' + (disciplineResponse.message || 'Unknown error'));
            showErrorState();
        }
    })
    .catch(error => {
        console.error('Error loading discipline:', error);
        showError('Failed to load discipline details');
        showErrorState();
    });
}

function showErrorState() {
    document.getElementById('loadingCard').innerHTML = `
        <div class="card-body text-center py-5">
            <i class="bi bi-exclamation-circle text-danger" style="font-size: 3rem;"></i>
            <p class="mt-3 text-danger">Failed to load discipline details</p>
            <a href="?route=disciplines" class="btn btn-primary">Back to Disciplines</a>
        </div>
    `;
}

function deleteDiscipline(disciplineId) {
    if (!confirm('Are you sure you want to delete this discipline? This action cannot be undone.')) {
        return;
    }
    
    fetch(`?route=api/admin/disciplines&id=${disciplineId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Discipline deleted successfully');
            // Redirect to disciplines list after a short delay
            setTimeout(() => {
                window.location.href = '?route=disciplines';
            }, 1500);
        } else {
            showError('Failed to delete discipline: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting discipline:', error);
        showError('Failed to delete discipline');
    });
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
$pageTitle = 'Discipline Details - ConstructLink™';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Master Data', 'url' => '#'],
    ['title' => 'Disciplines', 'url' => '?route=disciplines'],
    ['title' => 'View Details', 'url' => '?route=disciplines/view&id=' . ($disciplineId ?? '')]
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>