<?php
// Start output buffering to capture content
ob_start();

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
?>

<!-- Action Buttons (No Header - handled by layout) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Primary actions">
        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
            <a href="?route=makers/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-sm-inline">Add Manufacturer</span>
                <span class="d-sm-none">Add</span>
            </a>
        <?php endif; ?>
    </div>
    <div class="btn-toolbar gap-2" role="toolbar" aria-label="Secondary actions">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshMakers()">
            <i class="bi bi-arrow-clockwise"></i>
            <span class="d-none d-sm-inline ms-1">Refresh</span>
        </button>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['message'])): ?>
    <?php
    $messages = [
        'maker_created' => 'Manufacturer created successfully!',
        'maker_updated' => 'Manufacturer updated successfully!',
        'maker_deleted' => 'Manufacturer deleted successfully!'
    ];
    $message = $messages[$_GET['message']] ?? '';
    ?>
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--neutral-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-gear text-secondary fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Total Manufacturers</h6>
                        <h3 class="mb-0"><?= $makerStats['total_makers'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i>Registered makers
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
                        <h6 class="text-muted mb-1 small">Active Manufacturers</h6>
                        <h3 class="mb-0"><?= $makerStats['active_makers'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-check-circle me-1"></i>Currently active
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
                        <h6 class="text-muted mb-1 small">Total Assets</h6>
                        <h3 class="mb-0"><?= $makerStats['total_assets'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-layers me-1"></i>From all makers
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100" style="border-left: 4px solid var(--info-color);">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-light p-2 me-3">
                        <i class="bi bi-globe text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1 small">Countries</h6>
                        <h3 class="mb-0"><?= $makerStats['countries_represented'] ?? 0 ?></h3>
                    </div>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-geo-alt me-1"></i>Global coverage
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
        <form method="GET" action="?route=makers" class="row g-3">
            <div class="col-md-3">
                <label for="country" class="form-label">Country</label>
                <select class="form-select" id="country" name="country">
                    <option value="">All Countries</option>
                    <?php if (isset($countries) && is_array($countries)): ?>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country) ?>" 
                                    <?= ($_GET['country'] ?? '') === $country ? 'selected' : '' ?>>
                                <?= htmlspecialchars($country) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by manufacturer name, country, or description..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="?route=makers" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Makers Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Manufacturers & Brands</h6>
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
        <?php if (empty($makers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-gear display-1 text-muted"></i>
                <h5 class="mt-3 text-muted">No manufacturers found</h5>
                <p class="text-muted">Try adjusting your filters or add a new manufacturer.</p>
                <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                    <a href="?route=makers/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add First Manufacturer
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="makersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Manufacturer Name</th>
                            <th>Country</th>
                            <th>Assets Count</th>
                            <th>Total Value</th>
                            <th>Popular Categories</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($makers as $maker): ?>
                            <tr>
                                <td>
                                    <a href="?route=makers/view&id=<?= $maker['id'] ?>" class="text-decoration-none">
                                        #<?= $maker['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="bi bi-gear text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($maker['name']) ?></div>
                                            <?php if (!empty($maker['website'])): ?>
                                                <small class="text-muted">
                                                    <a href="<?= htmlspecialchars($maker['website']) ?>" target="_blank" class="text-decoration-none">
                                                        <i class="bi bi-link-45deg me-1"></i>Website
                                                    </a>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($maker['country'])): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($maker['country']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">
                                        <?= $maker['assets_count'] ?? 0 ?> assets
                                    </span>
                                </td>
                                <td>
                                    <strong><?= formatCurrency($maker['total_value'] ?? 0) ?></strong>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        <?= truncateText($maker['popular_categories'] ?? 'N/A', 50) ?>
                                    </div>
                                </td>
                                <td>
                                    <small><?= formatDate($maker['created_at']) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?route=makers/view&id=<?= $maker['id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasRole(['System Admin', 'Procurement Officer'])): ?>
                                            <a href="?route=makers/edit&id=<?= $maker['id'] ?>" 
                                               class="btn btn-outline-warning" title="Edit Manufacturer">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if (($maker['assets_count'] ?? 0) == 0): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteMaker(<?= $maker['id'] ?>)" title="Delete Manufacturer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-danger disabled" 
                                                        title="Cannot delete manufacturer with assets">
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
                <nav aria-label="Makers pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=makers&page=<?= $pagination['current_page'] - 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?route=makers&page=<?= $i ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?route=makers&page=<?= $pagination['current_page'] + 1 ?><?= http_build_query(array_filter($_GET, fn($k) => $k !== 'page' && $k !== 'route'), '', '&') ?>">
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
// Delete maker
function deleteMaker(makerId) {
    if (confirm('Are you sure you want to delete this manufacturer? This action cannot be undone.')) {
        fetch(`?route=api/makers/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ maker_id: makerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete manufacturer: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the manufacturer');
        });
    }
}

// Refresh makers
function refreshMakers() {
    window.location.reload();
}

// Export to Excel
function exportToExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('format', 'csv');
    window.location.href = '?route=makers/export&' + params.toString();
}

// Print table
function printTable() {
    window.print();
}

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[action="?route=makers"]');
    const filterInputs = filterForm.querySelectorAll('select[name="country"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = filterForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php
// Capture content and assign to variable
$content = ob_get_clean();

// Set page variables
$pageTitle = 'Manufacturer Management - ConstructLink™';
$pageHeader = 'Manufacturer Management';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => '?route=dashboard'],
    ['title' => 'Manufacturers', 'url' => '?route=makers']
];

// Include main layout
include APP_ROOT . '/views/layouts/main.php';
?>
