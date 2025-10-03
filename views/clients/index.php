<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$userRole = $user['role_name'] ?? 'Guest';
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
            <a href="?route=clients/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Client</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-people text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Clients</h6>
                        <h3 class="mb-0"><?= count($clients ?? []) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i>All registered clients
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--success-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-check-circle text-success fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active Clients</h6>
                        <h3 class="mb-0"><?= count(array_filter($clients ?? [], fn($c) => $c['is_active'] ?? true)) ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-person-check me-1"></i>Currently active
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--primary-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-box text-primary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Client-Supplied Assets</h6>
                        <h3 class="mb-0"><?= $clientStats['client_supplied_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-box-seam me-1"></i>Assets from clients
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-building text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Active Projects</h6>
                        <h3 class="mb-0"><?= $clientStats['active_projects'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-folder-open me-1"></i>Client projects
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="bi bi-funnel me-2"></i>Filters
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="?route=clients" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by client name or contact..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=clients" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Clients</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel me-1"></i>Export
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="printTable()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($clients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No clients found</h5>
                <p class="text-muted">Try adjusting your filters or add a new client.</p>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <a href="?route=clients/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add Client
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="clientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Contact Information</th>
                            <th>Supplied Assets</th>
                            <th>Projects</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <a href="?route=clients/view&id=<?= $client['id'] ?>" class="text-decoration-none">
                                        #<?= $client['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-people text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($client['name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <?= htmlspecialchars($client['contact_info']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= $client['supplied_assets_count'] ?? 0 ?> assets
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $client['projects_count'] ?? 0 ?> projects
                                    </span>
                                </td>
                                <td>
                                    <?php if ($client['is_active'] ?? true): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($client['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=clients/view&id=<?= $client['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=clients/edit&id=<?= $client['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Client">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if (($client['supplied_assets_count'] ?? 0) == 0 && ($client['projects_count'] ?? 0) == 0): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteClient(<?= $client['id'] ?>)" title="Delete Client">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-danger disabled" 
                                                        title="Cannot delete client with assets or projects">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Clients pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page'), '', '&') ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Delete client
function deleteClient(clientId) {
    if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
        fetch(`?route=api/clients/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ client_id: clientId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete client: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the client');
        });
    }
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?route=clients&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Client Management - ConstructLink™';
$pageHeader = 'Client Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Clients', 'url' => '?route=clients']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
