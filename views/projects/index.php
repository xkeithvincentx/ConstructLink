<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <!-- Primary Actions (Left) -->
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin'])): ?>
            <a href="?route=projects/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Project</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Projects</h6>
                        <h3 class="mb-0"><?= $projectStats['total_projects'] ?? 0 ?></h3>
                        <small class="opacity-75">All projects in system</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-building display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Projects</h6>
                        <h3 class="mb-0"><?= $projectStats['active_projects'] ?? 0 ?></h3>
                        <small class="opacity-75">Currently running</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Assets</h6>
                        <h3 class="mb-0"><?= $projectStats['total_assets'] ?? 0 ?></h3>
                        <small class="opacity-75">Across all projects</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-box display-6"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                Total Value
                            <?php else: ?>
                                Procurement Orders
                            <?php endif; ?>
                        </h6>
                        <h3 class="mb-0">
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                ₱<?= number_format($projectStats['total_asset_value'] ?? 0, 2) ?>
                            <?php else: ?>
                                <?= $projectStats['total_procurement_orders'] ?? 0 ?>
                            <?php endif; ?>
                        </h3>
                        <small class="opacity-75">
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                Asset investments
                            <?php else: ?>
                                Active orders
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="align-self-center">
                        <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                            <i class="bi bi-currency-dollar display-6"></i>
                        <?php else: ?>
                            <i class="bi bi-cart display-6"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Advanced Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=projects" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
            <div class="col-md-3">
                <label for="manager_id" class="form-label">Project Manager</label>
                <select class="form-select" id="manager_id" name="manager_id">
                    <option value="">All Managers</option>
                    <?php foreach ($projectManagers ?? [] as $manager): ?>
                        <option value="<?= $manager['id'] ?>" <?= ($_GET['manager_id'] ?? '') == $manager['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($manager['full_name']) ?> (<?= $manager['managed_projects_count'] ?> projects)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label for="date_from" class="form-label">Created From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            
            <div class="col-md-3">
                <label for="date_to" class="form-label">Created To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by project name, code, location, or description..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=projects" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                <button type="button" class="btn btn-outline-info" onclick="toggleAdvancedFilters()">
                    <i class="bi bi-gear me-1"></i>Advanced
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Projects Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">
            Projects 
            <?php if (!empty($projects)): ?>
                <span class="badge bg-light text-dark ms-2"><?= count($projects) ?> of <?= $pagination['total'] ?? 0 ?></span>
            <?php endif; ?>
        </h6>
        <div class="d-flex gap-2">
            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director'])): ?>
                <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </button>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="refreshData()">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($projects)): ?>
            <div class="text-center py-5">
                <i class="bi bi-building display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No projects found</h5>
                <p class="text-muted">Try adjusting your filters or create a new project.</p>
                <?php if ($auth->hasRole(['System Admin'])): ?>
                    <a href="?route=projects/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Project
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="projectsTable">
                    <thead>
                        <tr>
                            <th>Project Info</th>
                            <th>Manager</th>
                            <th>Status</th>
                            <th>Assets</th>
                            <th>Activity</th>
                            <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                <th>Value</th>
                            <?php endif; ?>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-building text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">
                                                <a href="?route=projects/view&id=<?= $project['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($project['name']) ?>
                                                </a>
                                            </div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($project['code']) ?> • 
                                                <?= htmlspecialchars(substr($project['location'], 0, 30)) ?><?= strlen($project['location']) > 30 ? '...' : '' ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($project['project_manager_name']): ?>
                                        <div class="small">
                                            <i class="bi bi-person-badge me-1"></i>
                                            <?= htmlspecialchars($project['project_manager_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Not assigned</span>
                                    <?php endif; ?>
                                    <?php if ($project['assigned_users_count'] > 0): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-people me-1"></i>
                                            <?= $project['assigned_users_count'] ?> assigned
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($project['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>
                                            <span class="badge bg-light text-dark">
                                                <?= $project['assets_count'] ?? 0 ?> total
                                            </span>
                                        </div>
                                        <?php if (($project['available_count'] ?? 0) > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-success">
                                                    <?= $project['available_count'] ?> available
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($project['in_use_count'] ?? 0) > 0): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-warning">
                                                    <?= $project['in_use_count'] ?> in use
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if (($project['withdrawals_count'] ?? 0) > 0): ?>
                                            <div>
                                                <i class="bi bi-arrow-down-circle me-1"></i>
                                                <?= $project['withdrawals_count'] ?> withdrawals
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($project['procurement_count'] ?? 0) > 0): ?>
                                            <div>
                                                <i class="bi bi-cart me-1"></i>
                                                <?= $project['procurement_count'] ?> orders
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($project['withdrawals_count'] ?? 0) == 0 && ($project['procurement_count'] ?? 0) == 0): ?>
                                            <span class="text-muted">No activity</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php if ($auth->hasRole(['System Admin', 'Finance Director', 'Asset Director', 'Procurement Officer'])): ?>
                                    <td>
                                        <div class="small">
                                            <?php if (($project['total_value'] ?? 0) > 0): ?>
                                                <strong>₱<?= number_format($project['total_value'], 2) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">₱0.00</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="small">
                                        <?= date('M j, Y', strtotime($project['created_at'])) ?>
                                        <div class="text-muted">
                                            <?= date('g:i A', strtotime($project['created_at'])) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=projects/view&id=<?= $project['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin'])): ?>
                                            <a href="?route=projects/edit&id=<?= $project['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Project">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteProject(<?= $project['id'] ?>)" title="Delete Project">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown" title="More Actions">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="?route=assets&project_id=<?= $project['id'] ?>">
                                                        <i class="bi bi-box me-2"></i>View Assets
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="?route=withdrawals&project_id=<?= $project['id'] ?>">
                                                        <i class="bi bi-arrow-down-circle me-2"></i>View Withdrawals
                                                    </a>
                                                </li>
                                                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="?route=procurement-orders&project_id=<?= $project['id'] ?>">
                                                            <i class="bi bi-cart me-2"></i>View Procurement
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Enhanced Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Projects pagination" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> to 
                            <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> 
                            of <?= $pagination['total'] ?> projects
                        </div>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Enhanced JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh every 5 minutes
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            refreshData();
        }
    }, 300000);
    
    // Date validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', validateDateRange);
        dateTo.addEventListener('change', validateDateRange);
    }
});

function validateDateRange() {
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        document.getElementById('date_to').setCustomValidity('End date must be after start date');
    } else {
        document.getElementById('date_to').setCustomValidity('');
    }
}

// Delete project with enhanced confirmation
function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project?\n\nThis action cannot be undone and will fail if the project has assigned assets or active procurement orders.')) {
        fetch(`?route=projects/delete&id=${projectId}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the project');
        });
    }
}

// Export to Excel with current filters
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=projects/export&' + params.toString();
}

// Print table
function printTable() {
    const printContent = document.getElementById('projectsTable').outerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Projects Report</title>
                <style>
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .btn-group { display: none; }
                </style>
            </head>
            <body>
                <h2>Projects Report - ${new Date().toLocaleDateString()}</h2>
                ${printContent}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Refresh data
function refreshData() {
    window.location.reload();
}

// Toggle advanced filters
function toggleAdvancedFilters() {
    // This could expand to show more filter options
    alert('Advanced filters feature coming soon!');
}

// Quick filter functions
function filterByStatus(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'f':
                e.preventDefault();
                document.getElementById('search').focus();
                break;
            case 'n':
                if (<?= $auth->hasRole(['System Admin']) ? 'true' : 'false' ?>) {
                    e.preventDefault();
                    window.location.href = '?route=projects/create';
                }
                break;
        }
    }
});
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Project Management - ConstructLink™';
$pageHeader = 'Project Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Projects', 'url' => '?route=projects']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
